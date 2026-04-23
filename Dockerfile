# syntax=docker/dockerfile:1.7

# ---------- Stage 1: build frontend assets ----------
FROM node:20-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json* vite.config.js ./
RUN npm install

COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- Stage 2: php-fpm + nginx runtime ----------
FROM php:8.3-fpm-alpine AS app

ENV APP_ROOT=/var/www/html \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

# System deps + PHP extension build deps
RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        git \
        curl \
        tini \
        icu-dev \
        libzip-dev \
        postgresql-dev \
        oniguruma-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        zip \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        bcmath \
        intl \
        exif \
        pcntl \
        opcache \
    && apk del --no-cache \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR $APP_ROOT

# App source
COPY . $APP_ROOT

# PHP deps (production)
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-progress

# Compiled frontend assets from the node stage
COPY --from=assets /app/public/build $APP_ROOT/public/build

# Runtime permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
             storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Server + process configs
COPY docker/nginx.conf        /etc/nginx/nginx.conf
COPY docker/php.ini           /usr/local/etc/php/conf.d/zzz-app.ini
COPY docker/php-fpm.conf      /usr/local/etc/php-fpm.d/zzz-app.conf
COPY docker/supervisord.conf  /etc/supervisord.conf
COPY docker/entrypoint.sh     /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
 && mkdir -p /run/nginx /var/log/supervisor

EXPOSE 8080

ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf", "-n"]
