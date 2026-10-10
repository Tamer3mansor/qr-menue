FROM serversideup/php:8.3-fpm-nginx

USER root

RUN install-php-extensions pdo_mysql mysqli intl gd

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

COPY --chown=www-data:www-data . .

RUN composer dump-autoload --no-dev --optimize --no-interaction

RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    AUTORUN_LARAVEL_MIGRATION_FORCE=true \
    AUTORUN_LARAVEL_STORAGE_LINK=true \
    AUTORUN_LARAVEL_ROUTE_CACHE=true \
    AUTORUN_LARAVEL_VIEW_CACHE=true \
    AUTORUN_LARAVEL_EVENT_CACHE=true \
    AUTORUN_LARAVEL_CONFIG_CACHE=true \
    AUTORUN_LARAVEL_OPTIMIZE=false \
    PHP_OPCACHE_ENABLE=1 \
    SSL_MODE=off

COPY --chmod=755 scripts/99-app-init.sh /etc/entrypoint.d/99-app-init.sh

USER www-data

EXPOSE 8080
