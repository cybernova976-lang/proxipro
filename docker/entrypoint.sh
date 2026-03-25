#!/bin/sh
# =============================================================================
# ProxiPro - Docker Entrypoint Script
# Handles runtime initialization (env, key, database, migrations)
# =============================================================================

set -e

# Create .env from environment variables if not mounted
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
    echo "Created .env from .env.example"
fi

# Generate APP_KEY if not already set via environment or .env
if [ -z "$APP_KEY" ] && ! grep -q "^APP_KEY=base64:" /var/www/html/.env 2>/dev/null; then
    php /var/www/html/artisan key:generate --force
    echo "Generated new APP_KEY"
fi

# Create SQLite database if using sqlite and file doesn't exist
if [ "$DB_CONNECTION" = "sqlite" ] || grep -q "^DB_CONNECTION=sqlite" /var/www/html/.env 2>/dev/null; then
    DB_PATH="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    if [ ! -f "$DB_PATH" ]; then
        touch "$DB_PATH"
        chown www-data:www-data "$DB_PATH"
        echo "Created SQLite database at $DB_PATH"
    fi
fi

# Run migrations
php /var/www/html/artisan migrate --force
echo "Migrations completed"

# Create storage link if not present
php /var/www/html/artisan storage:link --force 2>/dev/null || true

# Cache configuration for production
if [ "$APP_ENV" = "production" ]; then
    php /var/www/html/artisan config:cache
    php /var/www/html/artisan route:cache
    php /var/www/html/artisan view:cache
    echo "Production caches created"
fi

# Start supervisord (nginx + php-fpm + queue worker)
exec supervisord -c /etc/supervisord.conf
