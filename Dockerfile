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

# 1. Runtime packages — these stay in the final image (never removed).
#    Installed explicitly so they aren't "orphaned" dependencies of -dev
#    packages and accidentally swept away by `apk del` later.
RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        git \
        curl \
        tini \
        zip \
        unzip \
        icu-libs \
        libzip \
        libpq \
        libpng \
        libjpeg-turbo \
        libwebp \
        freetype \
        oniguruma

# 2. Build-time headers as a virtual group, compile extensions, wipe build deps.
#    After install, loop-verify every required extension and print an explicit
#    [OK]/[MISSING] line — avoids `grep -q`'s silent failure mode (which just
#    exits 255 with no output and wastes 3 minutes of debugging time).
RUN set -eu; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        postgresql-dev \
        oniguruma-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        bcmath \
        intl \
        exif \
        pcntl \
        opcache; \
    printf '\n=== Loaded PHP modules ===\n'; \
    php -m; \
    printf '\n=== Verifying required extensions ===\n'; \
    FAIL=0; \
    for ext in zip pdo_pgsql pgsql gd bcmath intl exif pcntl opcache; do \
        if php -m | grep -qxF "$ext"; then \
            printf '  [OK]      %s\n' "$ext"; \
        else \
            printf '  [MISSING] %s\n' "$ext"; \
            FAIL=1; \
        fi; \
    done; \
    if [ "$FAIL" -ne 0 ]; then \
        echo "One or more required PHP extensions failed to load. Aborting build."; \
        exit 1; \
    fi; \
    apk del .build-deps

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
