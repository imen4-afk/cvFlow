FROM php:8.2-apache

# Enable required PHP extensions
RUN docker-php-ext-install mysqli

# Enable Apache mod_rewrite for .htaccess
RUN a2enmod rewrite

# Allow .htaccess overrides
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Copy project files into web root
COPY . /var/www/html/

# Remove .env from the image (use Render's env vars instead)
RUN rm -f /var/www/html/.env

# Fix file permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
