FROM php:8.2-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache unzip libpq-dev \
    && docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/am_project

# Copy project files
COPY . .

# Install production dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose the port and start the built-in server
EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=8000
