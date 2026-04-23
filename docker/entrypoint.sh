#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

echo "[entrypoint] Booting apkbackoffice"

# Wait for Postgres if DB_HOST is set (Coolify-linked services may need a second or two)
if [[ "${DB_CONNECTION:-}" == "pgsql" && -n "${DB_HOST:-}" ]]; then
    echo "[entrypoint] Waiting for Postgres at ${DB_HOST}:${DB_PORT:-5432}..."
    for i in $(seq 1 30); do
        if php -r "exit(@fsockopen('${DB_HOST}', ${DB_PORT:-5432}) ? 0 : 1);" 2>/dev/null; then
            echo "[entrypoint] Postgres reachable."
            break
        fi
        sleep 1
    done
fi

# Ensure APP_KEY exists (first boot convenience)
if [[ -z "${APP_KEY:-}" ]]; then
    echo "[entrypoint] WARNING: APP_KEY env var not set. Generate one in Coolify env and redeploy."
fi

# Storage symlink (idempotent)
php artisan storage:link || true

# Clear stale caches from the build image before recaching
php artisan config:clear  || true
php artisan route:clear   || true
php artisan view:clear    || true

# Run migrations against the linked database
php artisan migrate --force --no-interaction

# Seed the admin user (idempotent — uses updateOrCreate against ADMIN_EMAIL)
php artisan db:seed --force --no-interaction --class=Database\\Seeders\\AdminUserSeeder || true

# Production caches (after migrate so any new config is picked up)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Filament v4 asset optimisation
php artisan filament:optimize || true

# Re-assert ownership in case a mounted volume came up root-owned
chown -R www-data:www-data storage bootstrap/cache || true

echo "[entrypoint] Startup tasks complete — handing off to: $*"
exec "$@"
