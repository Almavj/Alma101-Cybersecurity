### Backend Dockerfile for PHP API
# Uses the official PHP Apache image, installs composer, runs composer install
FROM php:8.2-apache

RUN apt-get update \
  && apt-get install -y --no-install-recommends git unzip libzip-dev zip libpng-dev libxml2-dev libonig-dev \
  && docker-php-ext-install pdo pdo_mysql mbstring xml \
  && rm -rf /var/lib/apt/lists/*

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files and install dependencies into /var/www/html
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist -o

# Copy backend source
COPY backend/ ./

# Serve backend from document root
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]
