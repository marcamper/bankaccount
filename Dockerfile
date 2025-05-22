# Use official PHP 8.1 Apache image as base
FROM php:8.1-apache

# Install system dependencies, PHP extensions and tools
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    zip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set Apache DocumentRoot to /var/www/html/public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Add Directory configuration for Apache
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy composer binary from official composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first to leverage Docker cache on dependencies install
COPY composer.json composer.lock* ./

# Install PHP dependencies (including dev packages like phpunit)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Copy application source files
COPY src/ src/
COPY public/ public/

# Expose port 80 for HTTP
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]