#!/bin/sh
set -e

cd /var/www/html

# Use PORT from Railway, default to 8080
export PORT="${PORT:-8080}"
export NGINX_PORT="${PORT}"

# Generate Nginx config with the correct port using envsubst
envsubst '${NGINX_PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Create .env file from .env.example if it doesn't exist
# (Required for artisan commands like key:generate to write APP_KEY)
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo ".env file created from .env.example"
    else
        touch .env
        echo "Empty .env file created"
    fi
fi

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force --no-interaction
fi

# Create SQLite database if using SQLite
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    DB_PATH="${DB_DATABASE:-database/database.sqlite}"
    if [ ! -f "$DB_PATH" ]; then
        echo "Creating SQLite database at ${DB_PATH}..."
        touch "$DB_PATH"
        chown www-data:www-data "$DB_PATH"
        chmod 664 "$DB_PATH"
    fi
fi

# Cache configuration for performance
php artisan package:discover --ansi --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# Run database migrations
php artisan migrate --force --no-interaction

# Create storage symlink
php artisan storage:link --force --no-interaction 2>/dev/null || true

# Fix permissions for runtime-writable directories only
chown -R www-data:www-data storage bootstrap/cache database

echo "Starting application on port ${PORT}..."

# Start Supervisor (manages Nginx + PHP-FPM)
exec /usr/bin/supervisord -c /etc/supervisord.conf
