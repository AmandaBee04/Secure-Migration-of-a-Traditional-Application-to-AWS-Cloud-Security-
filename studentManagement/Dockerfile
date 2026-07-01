FROM php:8.2-fpm

# Install system dependencies (Debian)
RUN apt-get update && apt-get install -y \
    git curl libonig-dev libxml2-dev libzip-dev zip unzip nginx \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (zip/bcmath removed — not needed, reduce crash surface)
RUN docker-php-ext-install pdo_mysql mbstring

# PHP config
RUN { \
    echo "memory_limit = 256M"; \
    echo "max_execution_time = 120"; \
    echo "opcache.enable=0"; \
    echo "opcache.enable_cli=0"; \
} >> /usr/local/etc/php/conf.d/custom.ini

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --no-interaction

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# PHP-FPM pool config (ondemand mode + access logging)
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf

# Debian nginx uses conf.d
RUN rm -f /etc/nginx/sites-enabled/default
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

COPY docker/start.sh /start.sh
COPY docker/seed.php /start-seed.php
RUN sed -i 's/\r$//' /start.sh && chmod +x /start.sh

CMD ["/start.sh"]
