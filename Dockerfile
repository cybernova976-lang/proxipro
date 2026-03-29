# ============================================================
# Stage 1 – Build frontend assets with Node.js
# ============================================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ============================================================
# Stage 2 – PHP runtime with Apache
# ============================================================
FROM php:8.2-apache AS runtime

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl libpng-dev libjpeg-dev libfreetype6-dev \
        libonig-dev libxml2-dev libzip-dev libsqlite3-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo pdo_sqlite pdo_mysql \
        mbstring xml zip gd bcmath intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# OPCache production settings
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set the Apache document root to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files and install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# Copy the rest of the application
COPY . .

# Re-run composer scripts (post-autoload-dump, etc.)
RUN composer dump-autoload --optimize

# Copy built frontend assets from stage 1
COPY --from=frontend /app/public/build ./public/build

# Copy the entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set correct permissions for Laravel storage & cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Create the SQLite database directory with correct permissions
RUN mkdir -p database && chown -R www-data:www-data database && chmod -R 775 database

# Default environment variables for production
# PORT=8080 is a fallback for local testing; Railway overrides it at runtime
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
