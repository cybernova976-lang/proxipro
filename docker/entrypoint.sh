#!/bin/sh
set -e

cd /var/www/html

# Use PORT from Railway, default to 8080
export PORT="${PORT:-8080}"

# Generate Nginx config with the correct port
sed -i "s/listen 8080;/listen ${PORT};/" /etc/nginx/nginx.conf

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
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

# Regenerate autoloader and discover packages
php artisan package:discover --ansi

# Cache configuration for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
php artisan migrate --force

# Create storage symlink
php artisan storage:link --force 2>/dev/null || true

# Ensure proper permissions
chown -R www-data:www-data storage bootstrap/cache database

echo "Starting application on port ${PORT}..."

# Start Supervisor (manages Nginx + PHP-FPM)
exec /usr/bin/supervisord -c /etc/supervisord.conf
