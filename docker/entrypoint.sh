#!/bin/bash
set -e

# -------------------------------------------------------
# ProxiPro – Railway / Docker entrypoint
# Handles first-run tasks before starting the web server
# -------------------------------------------------------

# 1. Generate APP_KEY if not already set
if [ -z "$APP_KEY" ]; then
    echo "⏳  Generating application key …"
    php artisan key:generate --force
fi

# 2. Ensure the SQLite database file exists (when using SQLite)
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    DB_PATH="${DB_DATABASE:-database/database.sqlite}"
    if [ ! -f "$DB_PATH" ]; then
        echo "⏳  Creating SQLite database at $DB_PATH …"
        touch "$DB_PATH"
    fi
fi

# 3. Cache configuration & routes for production
echo "⏳  Caching configuration …"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Run database migrations
echo "⏳  Running migrations …"
php artisan migrate --force

# 5. Create storage symlink if missing
if [ ! -L public/storage ]; then
    echo "⏳  Creating storage link …"
    php artisan storage:link
fi

echo "✅  Entrypoint complete – starting server"

# Execute the CMD passed by the Dockerfile (Apache or php artisan serve)
exec "$@"
