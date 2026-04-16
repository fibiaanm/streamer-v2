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
COPY docker/php/php.ini     /usr/local/etc/php/conf.d/app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf

# ─── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
