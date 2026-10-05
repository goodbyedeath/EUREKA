# EUREKA — one image, built from the repository, run as php-fpm.
#
# The discipline this image enforces is as important as what it contains: the application inside it is
# immutable. Today the live app is patched in place on shared hosting; here, a fix means a rebuild with a
# new tag. Nothing in a container is edited, and nothing that matters is written outside the mounted
# volumes (storage/) — see docker/README.md.

# ---------- 1. PHP dependencies ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# --no-dev is only safe because laravel/breeze, pail, sail and collision are listed in
# composer.json's extra.laravel.dont-discover. Without that list the autoloader drops them while
# bootstrap/cache/packages.php still names them, and the app dies at boot (CLAUDE.md).
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---------- 2. Front-end assets ----------
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
# Called directly rather than through npm scripts: the repo's scripts assume executable bits that do not
# survive every checkout (CLAUDE.md).
RUN node node_modules/vite/bin/vite.js build

# ---------- 3. The runtime ----------
FROM php:8.3-fpm-bookworm AS app

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
        libzip-dev libicu-dev libonig-dev \
        libmagickwand-dev \
        default-mysql-client \
        git unzip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd zip intl bcmath pdo_mysql opcache exif \
    && pecl install redis imagick \
    && docker-php-ext-enable redis imagick \
    && apt-get purge -y --auto-remove libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

# Livewire's JS is served as a static file and does not update with the package; publishing it at build
# time is what keeps "the published Livewire assets are out of date" out of the browser console (CLAUDE.md).
RUN php artisan livewire:publish --assets \
    && mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-eureka.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
