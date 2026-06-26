#!/usr/bin/env bash
# Prepare Laravel + HR Seva storage for local QA / Playwright runs.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

DB_FILE="$ROOT/database/database.sqlite"
mkdir -p "$ROOT/database" "$ROOT/storage/clients" "$ROOT/storage/framework/cache" "$ROOT/storage/framework/sessions" "$ROOT/storage/framework/views" "$ROOT/storage/logs"

if [[ ! -f "$DB_FILE" ]]; then
  touch "$DB_FILE"
  php artisan migrate --force --no-interaction
fi

# HR central DB is created on first API hit; prime health + auth seed.
curl -sf "http://127.0.0.1:${PORT:-8012}/api/health" >/dev/null 2>&1 || true

exec php artisan serve --host=127.0.0.1 --port="${PORT:-8012}"
