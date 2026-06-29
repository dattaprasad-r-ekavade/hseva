#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

DB_FILE="$ROOT/database/database.sqlite"
mkdir -p "$ROOT/database" "$ROOT/storage/clients" "$ROOT/storage/framework/cache" "$ROOT/storage/framework/sessions" "$ROOT/storage/framework/views" "$ROOT/storage/logs"

if [[ ! -f "$ROOT/.env" ]]; then
  cp "$ROOT/.env.example" "$ROOT/.env"
  php artisan key:generate --force --no-interaction
fi

if [[ ! -f "$DB_FILE" ]]; then
  touch "$DB_FILE"
  php artisan migrate --force --no-interaction
fi

exec php artisan serve --host=127.0.0.1 --port="${PORT:-8012}"
