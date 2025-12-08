FROM php:8.2-apache

# Install PostgreSQL PDO driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache rewrite (optional)
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

WORKDIR /var/www/html/

EXPOSE 80
