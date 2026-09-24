#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WORK_DIR="${RUNNER_TEMP:-/tmp}/apiblueprint-security-qa"
QA_DIR="$ROOT_DIR/storage/app/qa"
ROOT_LOG="$WORK_DIR/root-api.log"
GENERATED_LOG="$WORK_DIR/generated-api.log"
REPORT="$QA_DIR/security-qa-report.json"
ROOT_PID=""
GENERATED_PID=""

cleanup() {
    if [[ -n "$GENERATED_PID" ]]; then
        kill "$GENERATED_PID" 2>/dev/null || true
    fi
    if [[ -n "$ROOT_PID" ]]; then
        kill "$ROOT_PID" 2>/dev/null || true
    fi
}
trap cleanup EXIT

wait_for_url() {
    local url="$1"
    local log_file="$2"

    for _ in $(seq 1 40); do
        if curl -fsS "$url" >/dev/null 2>&1; then
            return 0
        fi
        sleep 0.5
    done

    echo "Timed out waiting for $url" >&2
    if [[ -f "$log_file" ]]; then
        tail -n 100 "$log_file" >&2 || true
    fi
    return 1
}

rm -rf "$WORK_DIR"
mkdir -p "$WORK_DIR/generated" "$QA_DIR"
rm -f "$REPORT"

cd "$ROOT_DIR"
php artisan serve --host=127.0.0.1 --port=18082 >"$ROOT_LOG" 2>&1 &
ROOT_PID=$!
wait_for_url "http://127.0.0.1:18082/api/v1/meta/status" "$ROOT_LOG"

python3 - deployment/security-qa-manifest.json "$WORK_DIR/hostile-manifest.json" <<'PY'
from pathlib import Path
import json
import sys

source = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
source["project"]["name"] = "../../evil\r\nX-Evil: injected"
Path(sys.argv[2]).write_text(json.dumps(source, ensure_ascii=False), encoding="utf-8")
PY

curl -fsS \
    -D "$WORK_DIR/hostile-headers.txt" \
    -X POST \
    -H 'Accept: application/zip' \
    -H 'Content-Type: application/json' \
    --data-binary '@'"$WORK_DIR/hostile-manifest.json" \
    -o "$WORK_DIR/hostile.zip" \
    'http://127.0.0.1:18082/api/v1/blueprint/export'

python3 - "$WORK_DIR/hostile.zip" "$WORK_DIR/hostile-headers.txt" <<'PY'
from pathlib import Path
from zipfile import ZipFile
import re
import sys

zip_path = Path(sys.argv[1])
headers = Path(sys.argv[2]).read_text(encoding="iso-8859-1")

payload = zip_path.read_bytes()
if payload[:2] != b"PK":
    raise SystemExit("Hostile-name export is not a ZIP archive")

if re.search(r"(?im)^X-Evil\s*:", headers):
    raise SystemExit("Hostile project name injected an HTTP response header")

match = re.search(r'(?im)^Content-Disposition:\s*attachment;\s*filename="([^"]+)"\s*$', headers)
if match is None:
    raise SystemExit("Export response is missing a safe Content-Disposition filename")

if match.group(1) != "evil-x-evil-injected.zip":
    raise SystemExit(f"Unexpected sanitized export filename: {match.group(1)}")

with ZipFile(zip_path) as archive:
    names = archive.namelist()
    if not names:
        raise SystemExit("Hostile-name export archive is empty")
    if any(".." in Path(name).parts for name in names):
        raise SystemExit("Generated archive contains a parent-traversal segment")
    if any(not name.startswith("evil-x-evil-injected/") for name in names):
        raise SystemExit("Generated archive escaped the sanitized project root")
PY

curl -fsS \
    -X POST \
    -H 'Accept: application/zip' \
    -H 'Content-Type: application/json' \
    --data-binary '@deployment/security-qa-manifest.json' \
    -o "$WORK_DIR/generated.zip" \
    'http://127.0.0.1:18082/api/v1/blueprint/export'

python3 - "$WORK_DIR/generated.zip" <<'PY'
from pathlib import Path
import sys

payload = Path(sys.argv[1]).read_bytes()
if payload[:2] != b"PK":
    raise SystemExit("Generated security QA solution is not a ZIP archive")
PY

unzip -q "$WORK_DIR/generated.zip" -d "$WORK_DIR/generated"
GENERATED_DIR="$(find "$WORK_DIR/generated" -mindepth 1 -maxdepth 1 -type d | head -n 1)"
test -n "$GENERATED_DIR"

cd "$GENERATED_DIR"
composer validate --strict
composer install --no-interaction --prefer-dist --no-progress
cp .env.example .env
php artisan key:generate
mkdir -p database
touch database/database.sqlite
cat >> .env <<EOF
DB_CONNECTION=sqlite
DB_DATABASE=$GENERATED_DIR/database/database.sqlite
EOF
php artisan migrate:fresh --force

php artisan serve --host=127.0.0.1 --port=18083 >"$GENERATED_LOG" 2>&1 &
GENERATED_PID=$!
wait_for_url "http://127.0.0.1:18083/up" "$GENERATED_LOG"

STATUS_1="$(curl -sS -o "$WORK_DIR/rate-1.json" -w '%{http_code}' -H 'Accept: application/json' -H 'X-Correlation-ID: security-rate-1' 'http://127.0.0.1:18083/api/v1/products')"
STATUS_2="$(curl -sS -o "$WORK_DIR/rate-2.json" -w '%{http_code}' -H 'Accept: application/json' -H 'X-Correlation-ID: security-rate-2' 'http://127.0.0.1:18083/api/v1/products')"
STATUS_3="$(curl -sS -D "$WORK_DIR/rate-3-headers.txt" -o "$WORK_DIR/rate-3.json" -w '%{http_code}' -H 'Accept: application/json' -H 'X-Correlation-ID: security-rate-3' 'http://127.0.0.1:18083/api/v1/products')"

python3 - \
    "$WORK_DIR/rate-3.json" \
    "$WORK_DIR/rate-3-headers.txt" \
    "$REPORT" \
    "$STATUS_1" "$STATUS_2" "$STATUS_3" <<'PY'
from datetime import datetime, timezone
from pathlib import Path
import json
import sys

body_path = Path(sys.argv[1])
headers_path = Path(sys.argv[2])
report_path = Path(sys.argv[3])
statuses = [int(sys.argv[4]), int(sys.argv[5]), int(sys.argv[6])]

if statuses != [200, 200, 429]:
    raise SystemExit(f"Rate-limit runtime sequence mismatch: {statuses}")

headers = headers_path.read_text(encoding="iso-8859-1").lower()
if "content-type: application/problem+json" not in headers:
    raise SystemExit("429 response is not application/problem+json")
if "x-correlation-id: security-rate-3" not in headers:
    raise SystemExit("429 response did not preserve correlation ID")

body = json.loads(body_path.read_text(encoding="utf-8"))
if body.get("status") != 429:
    raise SystemExit("429 Problem Details status mismatch")
if body.get("title") != "Demasiadas solicitudes":
    raise SystemExit("429 Problem Details title mismatch")

report = {
    "schema_version": "1.0",
    "generated_at": datetime.now(timezone.utc).isoformat(),
    "root_export": {
        "hostile_project_name_sanitized": True,
        "response_header_injection_rejected": True,
        "archive_path_traversal_rejected": True,
        "sanitized_filename": "evil-x-evil-injected.zip",
    },
    "generated_runtime": {
        "configured_requests_per_minute": 2,
        "status_sequence": statuses,
        "rate_limit_enforced": True,
        "problem_details_429": True,
        "correlation_preserved_on_429": True,
    },
}
report_path.parent.mkdir(parents=True, exist_ok=True)
report_path.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
PY

echo "BP-ADOPT-007B security QA PASS"
