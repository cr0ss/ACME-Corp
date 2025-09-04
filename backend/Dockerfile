# Multi-stage Dockerfile for Laravel Backend

# Base PHP image with extensions
FROM php:8.4-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    icu-dev \
    autoconf \
    g++ \
    make \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install APCu for user data caching
RUN pecl install apcu \
    && docker-php-ext-enable apcu

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Development stage
FROM base AS development

# Install Xdebug for development
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy application code
COPY . .

# Generate autoloader
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Create entrypoint script
RUN echo '#!/bin/sh' > /usr/local/bin/docker-entrypoint.sh \
    && echo 'set -e' >> /usr/local/bin/docker-entrypoint.sh \
    && echo '' >> /usr/local/bin/docker-entrypoint.sh \
    && echo '# Wait for database' >> /usr/local/bin/docker-entrypoint.sh \
    && echo 'echo "Waiting for database..."' >> /usr/local/bin/docker-entrypoint.sh \
    && echo 'until php artisan migrate:status > /dev/null 2>&1; do' >> /usr/local/bin/docker-entrypoint.sh \
    && echo '  echo "Database not ready, waiting..."' >> /usr/local/bin/docker-entrypoint.sh \
    && echo '  sleep 2' >> /usr/local/bin/docker-entrypoint.sh \
    && echo 'done' >> /usr/local/bin/docker-entrypoint.sh \
    && echo '' >> /usr/local/bin/docker-entrypoint.sh \
    && echo '# Run migrations and seed' >> /usr/local/bin/docker-entrypoint.sh \
    && echo 'php artisan migrate --force' >> /usr/local/bin/docker-entrypoint.sh \
    && echo 'php artisan db:seed --force' >> /usr/local/bin/docker-entrypoint.sh \
    && echo '' >> /usr/local/bin/docker-entrypoint.sh \
    && echo '# Clear and cache config' >> /usr/local/bin/docker-entrypoint.sh \
    && echo 'php artisan config:cache' >> /usr/local/bin/docker-entrypoint.sh \
    && echo 'php artisan route:cache' >> /usr/local/bin/docker-entrypoint.sh \
    && echo 'php artisan view:cache' >> /usr/local/bin/docker-entrypoint.sh \
    && echo '' >> /usr/local/bin/docker-entrypoint.sh \
    && echo '# Create storage link' >> /usr/local/bin/docker-entrypoint.sh \
    && echo 'php artisan storage:link' >> /usr/local/bin/docker-entrypoint.sh \
    && echo '' >> /usr/local/bin/docker-entrypoint.sh \
    && echo '# Start PHP-FPM' >> /usr/local/bin/docker-entrypoint.sh \
    && echo 'exec php-fpm' >> /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

USER www-data

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Production stage
FROM base AS production

# Copy composer files
COPY composer.json composer.lock ./

# Install production dependencies only
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Optimize Laravel for production
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

# Create production entrypoint
RUN echo '#!/bin/sh' > /usr/local/bin/docker-entrypoint-prod.sh \
    && echo 'set -e' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo '' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo '# Wait for database' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo 'until php artisan migrate:status > /dev/null 2>&1; do' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo '  sleep 2' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo 'done' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo '' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo '# Run migrations' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo 'php artisan migrate --force' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo '' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo '# Create storage link' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo 'php artisan storage:link' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo '' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo '# Start PHP-FPM' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && echo 'exec php-fpm' >> /usr/local/bin/docker-entrypoint-prod.sh \
    && chmod +x /usr/local/bin/docker-entrypoint-prod.sh

USER www-data

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-prod.sh"]
