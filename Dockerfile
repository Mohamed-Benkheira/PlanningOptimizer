# Use PHP 8.3 FPM
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    zip unzip git libicu-dev libzip-dev zlib1g-dev

# Install PHP extensions
RUN docker-php-ext-install intl pdo pdo_mysql zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy the entire project first
COPY . .

# Install dependencies and optimize autoloader
RUN composer install --no-dev --optimize-autoloader

# Expose port Railway will use
EXPOSE 8080

# Start Laravel
CMD php artisan serve --host=0.0.0.0 --port=$PORT
