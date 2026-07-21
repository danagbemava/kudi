#!/bin/sh
set -e

cd /app

if [ ! -f .env ]; then
  cp .env.example .env
  # `php artisan serve` does not forward container env vars to its PHP
  # worker, so bake the compose-provided values into .env (which Laravel
  # reads on every request).
  sed -i '/^SANCTUM_STATEFUL_DOMAINS=/d; /^FRONTEND_URL=/d; /^APP_URL=/d' .env
  {
    echo "APP_URL=${APP_URL:-http://localhost:8080}"
    echo "FRONTEND_URL=${FRONTEND_URL:-http://localhost:8080}"
    echo "SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-localhost:8080,localhost}"
  } >> .env
  php artisan key:generate --force -q
fi

# First boot: create and seed the SQLite database (kept on a volume).
if [ ! -s database/database.sqlite ]; then
  touch database/database.sqlite
  php artisan migrate --seed --force
else
  php artisan migrate --force
fi

exec php artisan serve --host 0.0.0.0 --port 8000
