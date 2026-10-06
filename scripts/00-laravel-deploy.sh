#!/bin/bash
set -e

cd /var/www/html

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

php artisan migrate --force

php artisan storage:link || true

php artisan db:seed --class=SuperAdminSeeder --force
php artisan db:seed --class=DemoSeeder --force

php-fpm83 -D
nginx -g "daemon off;"
