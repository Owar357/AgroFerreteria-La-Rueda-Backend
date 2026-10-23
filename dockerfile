FROM php:8.4.15-cli

WORKDIR /app

# Instalar PostgreSQL, herramientas ZIP y dependencias para GD (JPEG/PDF)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libpng-dev libjpeg-dev libfreetype6-dev zip unzip \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo pdo_pgsql bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

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
