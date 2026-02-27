#!/bin/sh
set -e

if [ ! -f /var/www/html/vendor/autoload.php ]; then
  echo "vendor not found, running composer install..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if ! grep -q '^APP_KEY=base64:' /var/www/html/.env 2>/dev/null; then
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
