FROM php:8.3-cli-alpine

# Dependencias del sistema
RUN apk add --no-cache \
    git \
    unzip \
    zip \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    mysql-client

# Configurar e instalar extensiones PHP
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    gd \
    zip \
    intl \
    bcmath

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copiar primero composer para mejor cache de layers
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Copiar resto del proyecto
COPY . .

# Preparar SQLite para demo
RUN mkdir -p database \
    && touch database/database.sqlite

# Limpiar caches Laravel
RUN php artisan config:clear || true \
    && php artisan cache:clear || true \
    && php artisan route:clear || true \
    && php artisan view:clear || true

EXPOSE 8080

# Migrar + seed solo al arrancar
CMD ["sh", "-c", "php artisan migrate --seed --force && php artisan serve --host=0.0.0.0 --port=8080"]