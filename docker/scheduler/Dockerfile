FROM php:8.3-fpm

# ─── System dependencies ──────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip \
    libzip-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libpq-dev \
    libicu-dev \
    libexif-dev \
    && rm -rf /var/lib/apt/lists/*

# ─── PHP extensions ───────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql pgsql \
        gd intl bcmath zip exif pcntl sockets opcache

RUN pecl install redis \
    && docker-php-ext-enable redis

# ─── PHP configuration ────────────────────────────────────────────────────────
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# ─── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["/bin/sh", "-c", "while true; do php artisan schedule:run --verbose --no-interaction; sleep 60; done"]
