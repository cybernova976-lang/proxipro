#!/bin/bash
# Do NOT use set -e: we want Apache to start even if setup commands fail
# -------------------------------------------------------
# ProxiPro – Railway / Docker entrypoint
# Handles first-run tasks before starting the web server
# -------------------------------------------------------

cd /var/www/html

# 0. Configure Apache to listen on $PORT (Railway injects it at runtime)
if [ -n "$PORT" ]; then
    echo "Configuring Apache to listen on port $PORT ..."
    sed -i "s/^Listen 80$/Listen $PORT/" /etc/apache2/ports.conf || true
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf || true
fi

# Ensure storage directories exist
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs 2>/dev/null || true
chown -R www-data:www-data storage 2>/dev/null || true

# 1. Create .env from .env.example if it does not exist
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        echo "Creating .env from .env.example ..."
        cp .env.example .env
    else
        echo "No .env.example found, creating empty .env ..."
        touch .env
    fi
fi

# 2. Generate APP_KEY if not already set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key ..."
    php artisan key:generate --force --no-interaction || echo "WARNING: key:generate failed"
fi

# 3. Ensure the SQLite database file exists (when using SQLite)
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    DB_PATH="${DB_DATABASE:-database/database.sqlite}"
    mkdir -p "$(dirname "$DB_PATH")"
    if [ ! -f "$DB_PATH" ]; then
        echo "Creating SQLite database at $DB_PATH ..."
        touch "$DB_PATH"
    fi
    chown www-data:www-data "$DB_PATH" 2>/dev/null || true
fi

# 4. Cache configuration & routes for production
#    These run at startup (not build time) because config:cache
#    bakes in runtime environment variables supplied by Railway.
echo "⏳  Caching configuration …"
php artisan config:cache --no-interaction || true
php artisan route:cache --no-interaction || true
php artisan view:clear --no-interaction || true
php artisan view:cache --no-interaction || true

# 5. Run database migrations
echo "⏳  Running migrations …"
php artisan migrate --force --no-interaction || echo "WARNING: Migrations failed, continuing..."

# 6. Run admin seeder
php artisan db:seed --class=AdminSeeder --force --no-interaction || true

# 7. Create storage symlink if missing
if [ ! -L public/storage ]; then
    echo "⏳  Creating storage link …"
    php artisan storage:link --force --no-interaction 2>/dev/null || true
fi

echo "✅  Entrypoint complete – starting server"

# Execute the CMD passed by the Dockerfile (Apache or php artisan serve)
exec "$@"
