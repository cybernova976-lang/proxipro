# =============================================================================
# Stage 1: Build frontend assets with Node.js
# =============================================================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN NODE_ENV=development npm ci

COPY vite.config.js ./
COPY resources ./resources
RUN NODE_ENV=production ./node_modules/.bin/vite build

# =============================================================================
# Stage 2: Install PHP dependencies with Composer
# =============================================================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader

# =============================================================================
# Stage 3: Production image with PHP-FPM + Nginx
# =============================================================================
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    sqlite-dev \
    postgresql-dev \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlite \
    gd \
    zip \
    intl \
    mbstring \
    bcmath \
    opcache \
    pcntl

# Configure OPcache for production
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Configure PHP for production
RUN { \
    echo 'upload_max_filesize=64M'; \
    echo 'post_max_size=64M'; \
    echo 'memory_limit=256M'; \
    echo 'max_execution_time=60'; \
    } > /usr/local/etc/php/conf.d/production.ini

WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy built frontend assets from stage 1
COPY --from=frontend /app/public/build ./public/build

# Copy vendor directory from stage 2
COPY --from=vendor /app/vendor ./vendor

# Copy Nginx config as template (envsubst replaces PORT at runtime)
COPY docker/nginx.conf /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf

# Copy and set up entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Create required directories and set permissions
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    database \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache database

# Remove dev/unnecessary files
RUN rm -rf \
    node_modules \
    tests \
    .git \
    .github \
    .env.example \
    phpunit.xml \
    vite.config.js \
    package.json \
    package-lock.json \
    composer.phar \
    docker \
    *.md

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
