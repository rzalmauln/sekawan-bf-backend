# ---------- Stage 1: Composer ----------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# ---------- Stage 2: PHP Build ----------
FROM php:8.3-fpm-alpine AS build

RUN apk add --no-cache \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    autoconf \
    gcc \
    g++ \
    make \
    pkgconfig

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl

RUN pecl install redis \
    && docker-php-ext-enable redis

# ---------- Stage 3: Runtime ----------
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    libpng \
    oniguruma \
    libxml2 \
    icu \
    freetype \
    libjpeg-turbo \
    bash

WORKDIR /var/www

# copy php extensions dari stage build
COPY --from=build /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=build /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# copy application
COPY . .

# copy vendor
COPY --from=vendor /app/vendor ./vendor

RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 storage bootstrap/cache

USER www-data

EXPOSE 9000

CMD ["php-fpm"]