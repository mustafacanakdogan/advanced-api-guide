#!/bin/sh
set -e

if [ -z "${APP_KEY:-}" ]; then
  echo "APP_KEY is missing, generating..."
  php artisan key:generate --force
else
  echo "APP_KEY already exists, skipping key generation."
fi

echo "Running migrations..."
php artisan migrate --force

echo "Seeding demo data..."
php artisan db:seed --force

exec "$@"
