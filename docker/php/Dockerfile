FROM php:8.3-fpm

# ─── System dependencies ──────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip \
    libzip-dev \
    libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    libpq-dev \
    libicu-dev \
    libexif-dev \
    && rm -rf /var/lib/apt/lists/*

# ─── PHP extensions ───────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql pgsql \
        gd intl bcmath zip exif pcntl sockets opcache

RUN pecl install redis \
    && docker-php-ext-enable redis

# ─── PHP configuration ────────────────────────────────────────────────────────
COPY docker/php/php.ini     /usr/local/etc/php/conf.d/app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf

# ─── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ─── Permisos de storage ──────────────────────────────────────────────────────
# www-data (php-fpm) necesita escribir en storage y bootstrap/cache.
# Se ejecuta en build time; el volumen montado en runtime hereda el ownership.
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs storage/app/media-tmp bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
