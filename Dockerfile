FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    acl fcgi file gettext git gnu-libiconv nodejs npm nginx

# ...extensiones PHP...

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --prefer-dist --no-dev --no-scripts --no-progress --no-interaction --optimize-autoloader

RUN mkdir -p var/cache var/log && chmod -R 777 var && chown -R www-data:www-data var

# Borra todo lo que haya antes en conf.d
RUN rm -f /etc/nginx/conf.d/*

# Copia tu config, pero mejor NO la llames default.conf por si acaso
COPY ./docker/nginx/default.conf /etc/nginx/conf.d/app.conf

EXPOSE 8080

CMD php-fpm & nginx -g "daemon off;"
