FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    acl fcgi file gettext git gnu-libiconv nodejs npm nginx

# ...instalación PHP...

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --prefer-dist --no-dev --no-scripts --no-progress --no-interaction --optimize-autoloader

RUN mkdir -p var/cache var/log && chmod -R 777 var && chown -R www-data:www-data var

# BORRA TODOS los .conf anteriores y copia solo el tuyo
RUN rm -f /etc/nginx/conf.d/*
COPY ./docker/nginx/app.conf /etc/nginx/conf.d/app.conf

# OPCIONAL: fuerza el nginx.conf correcto
COPY ./docker/nginx/nginx.conf /etc/nginx/nginx.conf

EXPOSE 8080

CMD php-fpm & nginx -g "daemon off;"
