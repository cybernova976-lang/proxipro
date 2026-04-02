#!/bin/bash
set -e

# -------------------------------------------------------
# ProxiPro – Railway / Docker entrypoint
# Handles first-run tasks before starting the web server
# -------------------------------------------------------

# 0. Configure Apache to listen on $PORT (Railway injects it at runtime)
if [ -n "$PORT" ]; then
    echo "⏳  Configuring Apache to listen on port $PORT …"
    sed -i "s/^Listen 80$/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf
fi

# 1. Create .env from .env.example if it does not exist
#    Provides sensible defaults and is required by key:generate
if [ ! -f .env ]; then
    echo "⏳  Creating .env from .env.example …"
    cp .env.example .env
fi

# 2. Generate APP_KEY if not already set
if [ -z "$APP_KEY" ]; then
    echo "⏳  Generating application key …"
    php artisan key:generate --force
fi

# 3. Ensure the SQLite database file exists (when using SQLite)
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    DB_PATH="${DB_DATABASE:-database/database.sqlite}"
    mkdir -p "$(dirname "$DB_PATH")"
    if [ ! -f "$DB_PATH" ]; then
        echo "⏳  Creating SQLite database at $DB_PATH …"
        touch "$DB_PATH"
    fi
    chown www-data:www-data "$DB_PATH"
fi

# 4. Cache configuration & routes for production
#    These run at startup (not build time) because config:cache
#    bakes in runtime environment variables supplied by Railway.
echo "⏳  Caching configuration …"
php artisan config:cache
php artisan route:cache
php artisan view:clear
php artisan view:cache

# 5. Run database migrations
echo "⏳  Running migrations …"
php artisan migrate --force

# 6. Create storage symlink if missing
if [ ! -L public/storage ]; then
    echo "⏳  Creating storage link …"
    php artisan storage:link
fi

echo "✅  Entrypoint complete – starting server"

# Execute the CMD passed by the Dockerfile (Apache or php artisan serve)
exec "$@"
