#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WORK_DIR="${RUNNER_TEMP:-/tmp}/apiblueprint-generated-acceptance"

rm -rf "$WORK_DIR"
mkdir -p "$WORK_DIR"

cases=(
  "blank:default"
  "crud:offset-no-rate"
  "saas:default"
  "commerce:default"
  "commerce:offset-no-rate"
)

for acceptance_case in "${cases[@]}"; do
  template="${acceptance_case%%:*}"
  profile="${acceptance_case##*:}"
  case_id="${template}-${profile}"
  archive="$WORK_DIR/${case_id}.zip"
  extract_dir="$WORK_DIR/${case_id}"

  echo "================================================="
  echo " Generated solution acceptance: ${template} (${profile})"
  echo "================================================="

  php "$ROOT_DIR/scripts/export-template.php" "$template" "$archive" "$profile"

  mkdir -p "$extract_dir"
  unzip -q "$archive" -d "$extract_dir"

  generated_root="$(find "$extract_dir" -mindepth 1 -maxdepth 1 -type d -print -quit)"
  if [[ -z "$generated_root" ]]; then
    echo "Generated archive does not contain a project directory." >&2
    exit 1
  fi

  test -f "$generated_root/composer.json"
  test -f "$generated_root/.apiblueprint.json"
  test -f "$generated_root/openapi/openapi.yaml"
  test -f "$generated_root/artisan"

  python3 "$ROOT_DIR/scripts/validate-generated-openapi.py" "$generated_root"

  (
    cd "$generated_root"

    composer validate --strict --no-check-lock
    composer install --no-interaction --prefer-dist --no-progress

    cp .env.example .env
    php artisan key:generate --ansi

    vendor/bin/pint --test
    php artisan test

    if [[ "$template" == "blank" ]]; then
      if grep -q 'Route::' routes/api.php; then
        echo "Blank template unexpectedly contains API routes." >&2
        exit 1
      fi
      php artisan route:list
    else
      php artisan route:list --path=api/v1
    fi
  )

done

echo "================================================="
echo " Generated solution acceptance: PASS"
echo "================================================="
