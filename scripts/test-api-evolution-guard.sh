#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASELINE="$ROOT_DIR/.blueprint/api-contract-baseline.json"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

cd "$ROOT_DIR"

php scripts/api-evolution.php validate "$BASELINE"

DRIFT_BASELINE="$TMP_DIR/api-contract-baseline.drift.json"
VALID_IMPACT="$TMP_DIR/API-IMPACT-999.json"
INVALID_IMPACT="$TMP_DIR/API-IMPACT-998.json"

php -r '
$baseline = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$baseline["contract_revision"] = str_repeat("a", 64);
$baseline["operations"][0]["contract_sha256"] = str_repeat("b", 64);
file_put_contents($argv[2], json_encode($baseline, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
' "$BASELINE" "$DRIFT_BASELINE"

if php scripts/api-evolution.php validate "$DRIFT_BASELINE" >/dev/null 2>&1; then
    echo "Expected ungoverned contract drift to fail." >&2
    exit 1
fi

CURRENT_REVISION="$(php -r '$b=json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR); echo $b["contract_revision"];' "$BASELINE")"

php -r '
$report = [
    "schema_version" => "0.5.4",
    "change_id" => "API-IMPACT-999",
    "previous_revision" => str_repeat("a", 64),
    "new_revision" => $argv[2],
    "classification" => "operation_local",
    "changed_operation_ids" => ["auth_login"],
    "changed_contract_areas" => ["other"],
    "affected_slices" => [],
    "revalidation_policy" => "affected_only",
    "preserve_unrelated_evidence" => true,
    "evidence_ids" => ["EVD-BPADOPT005-SELFTEST"],
];
file_put_contents($argv[1], json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
' "$VALID_IMPACT" "$CURRENT_REVISION"

API_IMPACT_FILE="$VALID_IMPACT" php scripts/api-evolution.php validate "$DRIFT_BASELINE"

php -r '
$report = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$report["change_id"] = "API-IMPACT-998";
$report["revalidation_policy"] = "project";
file_put_contents($argv[2], json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
' "$VALID_IMPACT" "$INVALID_IMPACT"

if API_IMPACT_FILE="$INVALID_IMPACT" php scripts/api-evolution.php validate "$DRIFT_BASELINE" >/dev/null 2>&1; then
    echo "Expected invalid impact policy to fail." >&2
    exit 1
fi

echo "API evolution guard self-test PASS."
