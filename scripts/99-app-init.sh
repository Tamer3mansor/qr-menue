#!/bin/sh
set -e

cd /var/www/html

php artisan db:seed --class=SuperAdminSeeder --force
php artisan db:seed --class=DemoSeeder --force

exit 0
