FROM richarvey/nginx-php-fpm:8.3

RUN apk add --no-cache \
    php83-pdo_mysql \
    php83-mysqli \
    ca-certificates \
    && update-ca-certificates

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

COPY . .

RUN php artisan key:generate --force || true

COPY docker/nginx/default.conf /etc/nginx/sites-available/default.conf

COPY scripts/00-laravel-deploy.sh /usr/local/bin/00-laravel-deploy.sh
RUN chmod +x /usr/local/bin/00-laravel-deploy.sh

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/00-laravel-deploy.sh"]
