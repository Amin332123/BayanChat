FROM php:8.2-fpm-alpine

# Install system dependencies and build tools needed for PHP extensions
RUN apk add --no-cache \
    unzip \
    libpq-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    bash

# Install and configure essential PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/am_project

# Copy project files
COPY . .

# Install production dependencies (ignoring platform requirements to bypass strict local environment checks)
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-scripts

# Ensure .env exists and generate app key
RUN cp .env.example .env \
    && php artisan key:generate --ansi

# Set permissions for storage and cache
RUN chmod -R 775 storage bootstrap/cache

# Expose the port and start the server
EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=8000
