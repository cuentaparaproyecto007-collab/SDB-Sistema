FROM php:8.2-fpm-alpine

# Instalar utilitarios del sistema y librerías de desarrollo
RUN apk add --no-cache \
    bash \
    libpq-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git

# Instalar extensiones nativas de PHP indispensables para Laravel y PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pgsql gd zip bcmath

# Importar la herramienta Composer desde su contenedor oficial estable
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Mover el código fuente al espacio de trabajo del contenedor
COPY . .

# Instalar dependencias limpias de Laravel optimizando el cargador automático
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Asegurar los privilegios de escritura para las cachés y el motor transaccional
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8000

# Desplegar el servidor de desarrollo exponiéndolo a la red virtual de Docker
CMD php artisan serve --host=0.0.0.0 --port=8000