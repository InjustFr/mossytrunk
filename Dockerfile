FROM dunglas/frankenphp:1-php8.5-bookworm

RUN install-php-extensions pdo_pgsql intl zip opcache apcu

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    SERVER_NAME=":80"

COPY docker/php/app.ini $PHP_INI_DIR/conf.d/zz-app.ini

WORKDIR /app
