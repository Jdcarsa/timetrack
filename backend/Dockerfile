FROM php:8.3-cli-alpine

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

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

RUN php artisan config:clear || true
RUN php artisan cache:clear || true

EXPOSE 8080

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=8080"]