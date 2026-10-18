FROM php:8.4.15-cli

WORKDIR /app

#  Instalar el conector de PostgreSQL y la herramienta zip
RUN apt-get update && apt-get install -y libpq-dev zip unzip \
    && docker-php-ext-install pdo pdo_pgsql bcmath

# Traer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar dependencias y descargarlas
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts

# Copiar el código del proyecto
COPY . .

#  Puerto y comando de arranque
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
