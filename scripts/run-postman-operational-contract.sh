#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WORK_DIR="${RUNNER_TEMP:-/tmp}/apiblueprint-operational-contract"
QA_DIR="$ROOT_DIR/storage/app/qa"
ROOT_LOG="$WORK_DIR/root-api.log"
GENERATED_LOG="$WORK_DIR/generated-api.log"
RAW_NEWMAN_REPORT="$WORK_DIR/newman-raw.json"
SANITIZED_NEWMAN_REPORT="$QA_DIR/newman-report.json"
AUDIT_REPORT="$QA_DIR/audit-runtime-report.json"
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
rm -f "$SANITIZED_NEWMAN_REPORT" "$AUDIT_REPORT"

cd "$ROOT_DIR"
php artisan serve --host=127.0.0.1 --port=18080 >"$ROOT_LOG" 2>&1 &
ROOT_PID=$!
wait_for_url "http://127.0.0.1:18080/api/v1/meta/status" "$ROOT_LOG"

curl -fsS \
    -X POST \
    -H 'Accept: application/zip' \
    -H 'Content-Type: application/json' \
    --data-binary '@deployment/operational-contract-manifest.json' \
    -o "$WORK_DIR/generated.zip" \
    'http://127.0.0.1:18080/api/v1/blueprint/export'

python3 - "$WORK_DIR/generated.zip" <<'PY'
from pathlib import Path
import sys

payload = Path(sys.argv[1]).read_bytes()
if payload[:2] != b"PK":
    raise SystemExit("Generated operational contract is not a ZIP archive")
PY

unzip -q "$WORK_DIR/generated.zip" -d "$WORK_DIR/generated"
GENERATED_DIR="$(find "$WORK_DIR/generated" -mindepth 1 -maxdepth 1 -type d | head -n 1)"
test -n "$GENERATED_DIR"

cd "$GENERATED_DIR"
composer validate --strict
composer install --no-interaction --prefer-dist --no-progress
cp .env.example .env
php artisan key:generate
cat >> .env <<EOF
DB_CONNECTION=sqlite
DB_DATABASE=$GENERATED_DIR/database/database.sqlite
EOF
mkdir -p database
touch database/database.sqlite
php artisan migrate:fresh --force

cat > .bp-operational-seed.php <<'PHP'
<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\DB::table('users')->insert([
    [
        'name' => 'Postman Admin',
        'email' => 'postman-admin@example.test',
        'password' => Illuminate\Support\Facades\Hash::make('PostmanSynthetic!123'),
        'role' => 'admin',
    ],
    [
        'name' => 'Postman User',
        'email' => 'postman-user@example.test',
        'password' => Illuminate\Support\Facades\Hash::make('PostmanSynthetic!123'),
        'role' => 'user',
    ],
]);

Illuminate\Support\Facades\DB::table('products')->insert([
    'id' => 'prod-001',
    'name' => 'Producto sintético Postman',
]);
PHP
php .bp-operational-seed.php
rm .bp-operational-seed.php
rm -f storage/logs/audit.jsonl

php artisan serve --host=127.0.0.1 --port=18081 >"$GENERATED_LOG" 2>&1 &
GENERATED_PID=$!
wait_for_url "http://127.0.0.1:18081/up" "$GENERATED_LOG"

cd "$ROOT_DIR"
npx --yes newman@6.2.1 --version
set +e
npx --yes newman@6.2.1 run \
    postman/ApiBlueprint.generated.postman_collection.json \
    --environment postman/ApiBlueprint.local.postman_environment.json \
    --reporters cli,json \
    --reporter-json-export "$RAW_NEWMAN_REPORT" \
    --color off
NEWMAN_STATUS=$?
set -e

python3 - "$RAW_NEWMAN_REPORT" "$SANITIZED_NEWMAN_REPORT" "$NEWMAN_STATUS" <<'PY'
from datetime import datetime, timezone
from pathlib import Path
import json
import re
import sys

raw_path = Path(sys.argv[1])
out_path = Path(sys.argv[2])
exit_code = int(sys.argv[3])

summary = {
    "schema_version": "1.0",
    "tool": "newman",
    "tool_version": "6.2.1",
    "generated_at": datetime.now(timezone.utc).isoformat(),
    "exit_code": exit_code,
    "stats": {},
    "executions": [],
    "failures": [],
}

if raw_path.exists():
    raw = json.loads(raw_path.read_text(encoding="utf-8"))
    run = raw.get("run", {})
    for name, stat in run.get("stats", {}).items():
        if isinstance(stat, dict):
            summary["stats"][name] = {
                "total": stat.get("total", 0),
                "pending": stat.get("pending", 0),
                "failed": stat.get("failed", 0),
            }

    for execution in run.get("executions", []):
        item = execution.get("item", {})
        response = execution.get("response", {})
        assertions = execution.get("assertions", []) or []
        summary["executions"].append({
            "name": item.get("name"),
            "status_code": response.get("code"),
            "assertions": len(assertions),
            "failed_assertions": sum(1 for assertion in assertions if assertion.get("error")),
        })

    for failure in run.get("failures", []):
        error = failure.get("error", {}) or {}
        parent = failure.get("parent", {}) or {}
        source = failure.get("source", {}) or {}
        message = str(error.get("message", ""))
        message = re.sub(r"Bearer\s+[A-Za-z0-9._~+\-/=]+", "Bearer [REDACTED]", message, flags=re.IGNORECASE)
        for secret in ["PostmanSynthetic!123", "PostmanSynthetic!456", "PostmanSynthetic!789", "wrong-synthetic-password"]:
            message = message.replace(secret, "[REDACTED]")
        summary["failures"].append({
            "parent": parent.get("name"),
            "source": source.get("name"),
            "error_name": error.get("name"),
            "message": message,
        })

out_path.parent.mkdir(parents=True, exist_ok=True)
out_path.write_text(json.dumps(summary, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
PY
rm -f "$RAW_NEWMAN_REPORT"

python3 - \
    "$GENERATED_DIR/storage/logs/audit.jsonl" \
    "$ROOT_DIR/postman/operation-coverage.json" \
    "$AUDIT_REPORT" <<'PY'
from datetime import datetime, timezone
from pathlib import Path
import json
import sys

audit_path = Path(sys.argv[1])
coverage_path = Path(sys.argv[2])
report_path = Path(sys.argv[3])

if not audit_path.exists():
    raise SystemExit("Generated runtime did not produce storage/logs/audit.jsonl")

records = [json.loads(line) for line in audit_path.read_text(encoding="utf-8").splitlines() if line.strip()]
coverage = json.loads(coverage_path.read_text(encoding="utf-8"))
expected = sorted({operation["audit_event"] for operation in coverage["operations"]})
observed = sorted({str(record.get("event_code")) for record in records if record.get("event_code")})
missing = sorted(set(expected) - set(observed))
required_fields = {
    "event_code",
    "occurred_at",
    "route",
    "method",
    "path",
    "status",
    "outcome",
    "correlation_id",
    "actor_id",
    "target_id",
}
semantic_records = [record for record in records if record.get("event_code") in expected]
malformed = [record.get("event_code") for record in semantic_records if not required_fields.issubset(record)]
empty_correlation = [record.get("event_code") for record in semantic_records if not record.get("correlation_id")]
raw_lower = audit_path.read_text(encoding="utf-8").lower()
sensitive_markers = ['"password"', '"authorization"', '"access_token"', '"refresh_token"', 'bearer ']
sensitive_present = [marker for marker in sensitive_markers if marker in raw_lower]

report = {
    "schema_version": "1.0",
    "generated_at": datetime.now(timezone.utc).isoformat(),
    "record_count": len(records),
    "expected_event_codes": expected,
    "observed_event_codes": observed,
    "missing_event_codes": missing,
    "malformed_event_codes": malformed,
    "empty_correlation_event_codes": empty_correlation,
    "sensitive_markers_present": sensitive_present,
}
report_path.parent.mkdir(parents=True, exist_ok=True)
report_path.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")

if missing:
    raise SystemExit(f"Missing semantic audit events: {missing}")
if malformed:
    raise SystemExit(f"Malformed semantic audit events: {malformed}")
if empty_correlation:
    raise SystemExit(f"Audit events without correlation id: {empty_correlation}")
if sensitive_present:
    raise SystemExit(f"Sensitive audit markers detected: {sensitive_present}")
PY

if [[ "$NEWMAN_STATUS" -ne 0 ]]; then
    echo "Newman operational contract failed with exit code $NEWMAN_STATUS" >&2
    exit "$NEWMAN_STATUS"
fi

echo "BP-ADOPT-004 operational contract acceptance PASS"
