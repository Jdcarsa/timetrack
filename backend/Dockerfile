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

# Extensiones PHP
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

# 1. Copiar composer primero para cache
COPY composer.json composer.lock ./

# 2. Instalar deps SIN scripts (porque aún no existe todo Laravel)
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-req=ext-gd \
    --no-scripts

# 3. Copiar TODO el proyecto
COPY . .

# 4. Ahora sí scripts Laravel
RUN php artisan package:discover --ansi || true
RUN php artisan config:clear || true
RUN php artisan cache:clear || true

EXPOSE 8080

CMD ["sh", "-c", "php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=8080"]