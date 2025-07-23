FROM php:8.3-fpm-alpine

# Instalar dependencias
RUN apk add --no-cache \
    acl \
    fcgi \
    file \
    gettext \
    git \
    gnu-libiconv \
    nodejs \
    npm

# Instalar extensiones de PHP
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

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/memory_limit = 128M/memory_limit = 512M/g' "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# Copiar composer files primero para cache layer
COPY composer.json composer.lock ./

# Arreglar problema de git ownership y instalar dependencias
RUN git config --global --add safe.directory /var/www/html \
    && composer install --prefer-dist --no-dev --no-scripts --no-progress --no-interaction --optimize-autoloader

# Copiar el resto de archivos de la aplicación
COPY . .

# Configurar permisos
RUN mkdir -p var/cache var/log \
    && chmod -R 777 var \
    && chown -R www-data:www-data var

# Exponer puerto
EXPOSE 9000

CMD ["php-fpm"] 