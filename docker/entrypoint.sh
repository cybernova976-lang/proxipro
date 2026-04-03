#!/bin/bash
# Do NOT use set -e: we want Apache to start even if setup commands fail
# -------------------------------------------------------
# ProxiPro – Railway / Docker entrypoint
# Handles first-run tasks before starting the web server
# -------------------------------------------------------

cd /var/www/html

# 0. Configure Apache to listen on $PORT (Railway injects it at runtime)
PORT="${PORT:-8080}"
echo "Configuring Apache to listen on port $PORT ..."

# Overwrite ports.conf entirely (more reliable than sed)
echo "Listen $PORT" > /etc/apache2/ports.conf

# Overwrite the default site VirtualHost to use $PORT
cat > /etc/apache2/sites-available/000-default.conf <<VHOST
<VirtualHost *:${PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
VHOST

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

echo "Apache listen configuration:"
grep -nE '^Listen ' /etc/apache2/ports.conf || true
grep -nE '<VirtualHost' /etc/apache2/sites-available/000-default.conf || true
apache2ctl -t || true

run_laravel_bootstrap() {
    echo "⏳  Background Laravel bootstrap started"

    # These tasks are non-critical for initial HTTP readiness.
    php artisan config:cache --no-interaction || echo "WARNING: config:cache failed"
    php artisan route:cache --no-interaction || echo "WARNING: route:cache failed"
    php artisan view:clear --no-interaction || echo "WARNING: view:clear failed"
    php artisan view:cache --no-interaction || echo "WARNING: view:cache failed"

    echo "⏳  Running migrations in background …"
    php artisan migrate --force --no-interaction || echo "WARNING: migrations failed"

    php artisan db:seed --class=AdminSeeder --force --no-interaction || echo "WARNING: AdminSeeder failed"

    if [ ! -L public/storage ]; then
        echo "⏳  Creating storage link …"
        php artisan storage:link --force --no-interaction 2>/dev/null || echo "WARNING: storage:link failed"
    fi

    echo "✅  Background Laravel bootstrap complete"
}

run_laravel_bootstrap &

echo "✅  Entrypoint complete – starting server immediately"

# Execute the CMD passed by the Dockerfile (Apache or php artisan serve)
exec "$@"
