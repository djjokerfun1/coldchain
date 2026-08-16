FROM php:8.4-fpm-alpine

RUN apk add --no-cache libpq icu-libs \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev icu-dev linux-headers \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl bcmath opcache pcntl \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-coldchain.ini

WORKDIR /var/www/html
