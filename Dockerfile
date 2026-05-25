FROM php:8.1-apache

# Hugging Face Spaces require the application to listen on port 7860
RUN sed -i 's/80/7860/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Enable Apache mod_rewrite for nice URLs if needed
RUN a2enmod rewrite

# Change the Document Root to the public folder where our API lives
ENV APACHE_DOCUMENT_ROOT /app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Install dependencies required by the library (zip is good for composer)
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    git \
    && docker-php-ext-install zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /app

# Copy the application files
COPY . .

# Adjust permissions so Apache can access the files
RUN chown -R www-data:www-data /app

# Switch to the www-data user to install composer dependencies securely
USER www-data
RUN composer install --no-dev --optimize-autoloader

# Switch back to root to run Apache
USER root

# Expose the Hugging Face port
EXPOSE 7860

# Start the Apache server
CMD ["apache2-foreground"]
