# Etapa única: PHP-FPM + Nginx para Symfony en Railway
FROM php:8.3-fpm-alpine

# Instala dependencias del sistema y Nginx
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

# Instala extensiones PHP necesarias
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

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copia el código completo de la app
COPY . .

# (Debug) Lista los archivos públicos, útil para ver en logs de Railway
RUN ls -l /var/www/html/public

# Instala dependencias PHP (solo producción)
RUN composer install --prefer-dist --no-dev --no-scripts --no-progress --no-interaction --optimize-autoloader

# Configura permisos
RUN mkdir -p var/cache var/log \
    && chmod -R 777 var \
    && chown -R www-data:www-data var

# Elimina cualquier conf anterior de nginx (¡importante!)
RUN rm -f /etc/nginx/conf.d/*

# Copia tu archivo nginx ya corregido (sin bloques globales ni duplicidad)
COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Expón el puerto 8080 para Railway
EXPOSE 8080

# Lanza ambos servicios en primer plano
CMD php-fpm & nginx -g "daemon off;"
