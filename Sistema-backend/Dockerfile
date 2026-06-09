FROM php:8.2-alpine

# Instalar dependencias del sistema y controladores para PostgreSQL
RUN apk add --no-cache zip unzip git bash libpng-dev libzip-dev openssl-dev postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql bcmath zip gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction --optimize-autoloader

EXPOSE 8000

CMD php artisan key:generate && php artisan serve --host=0.0.0.0 --port=8000