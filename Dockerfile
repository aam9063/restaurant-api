# Etapa 1: Build de dependencias PHP
FROM php:8.3-fpm-alpine as symfony_php

RUN apk add --no-cache \
    acl \
    fcgi \
    file \
    gettext \
    git \
    gnu-libiconv \
    nodejs \
    npm \
    nginx

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && apk add --no-cache \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        zlib-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        pdo_mysql \
        zip \
    && pecl install apcu \
    && docker-php-ext-enable apcu opcache \
    && apk del .build-deps

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copia el código completo
COPY . .

# (Debug) Verifica que está el public/index.php
RUN ls -l /var/www/html/public

# Instala dependencias PHP
RUN composer install --prefer-dist --no-dev --no-scripts --no-progress --no-interaction --optimize-autoloader

# Permisos
RUN mkdir -p var/cache var/log \
    && chmod -R 777 var \
    && chown -R www-data:www-data var

# Nginx config
COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Quita el default.conf que mete la imagen base de nginx
RUN rm -f /etc/nginx/conf.d/default.conf.default || true

# Exponer el puerto 8080
EXPOSE 8080

# Lanzar ambos procesos: PHP-FPM y NGINX
CMD php-fpm & nginx -g "daemon off;"
