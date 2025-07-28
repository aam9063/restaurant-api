FROM php:8.3-cli

WORKDIR /app

# Instala dependencias del sistema necesarias para Composer y Symfony
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    && docker-php-ext-install zip

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia tu proyecto
COPY . .

# Instala dependencias
RUN composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
