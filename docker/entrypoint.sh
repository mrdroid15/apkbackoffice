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

# Nuke every cache file baked into the image before any new work. This keeps
# us deterministic across redeploys — no stale routes-v7.php from the build
# image surviving into the running container.
php artisan optimize:clear

# Run migrations against the linked database
php artisan migrate --force --no-interaction

# Seed the admin user (idempotent — uses updateOrCreate against ADMIN_EMAIL)
php artisan db:seed --force --no-interaction --class=Database\\Seeders\\AdminUserSeeder || true

# Production caches.
#
# WHY route:cache IS DELIBERATELY ABSENT:
# Filament v4 registers Livewire components (e.g. filament.auth.pages.login)
# inside PanelsServiceProvider::boot(). route:cache compiles route files once
# in CLI and writes a snapshot; but the Livewire component *registry* isn't
# persisted the same way. After route:cache, POST /livewire/update can throw
# ComponentNotFoundException for auth components on fresh container boot.
# Skipping route:cache costs ~10ms per request (fine for an admin panel) and
# eliminates this entire failure mode. Re-enable only if you audit Livewire
# component resolution end-to-end.
php artisan config:cache
php artisan view:cache

# Filament asset + icon + component cache. Must run AFTER config:cache so it
# reads the final config; runs without `|| true` because a failure here means
# the panel will 500 at login time — louder is better than silent.
php artisan filament:optimize

# Re-assert ownership in case a mounted volume came up root-owned
chown -R www-data:www-data storage bootstrap/cache || true

echo "[entrypoint] Startup tasks complete — handing off to: $*"
exec "$@"
