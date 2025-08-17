FROM php:8.3-fpm

# ----------------------------------------
# 1. Install system dependencies
# ----------------------------------------
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    supervisor \
    gnupg2 \
    ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl gd

# ----------------------------------------
# 2. Install Node.js & npm
# ----------------------------------------
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# ----------------------------------------
# 3. Install Composer
# ----------------------------------------
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# ----------------------------------------
# 4. Set working directory
# ----------------------------------------
WORKDIR /var/www

# ----------------------------------------
# 5. Copy Laravel app source
# ----------------------------------------
COPY . .

# ----------------------------------------
# 6. Prepare Laravel cache paths & permissions
RUN mkdir -p storage/framework/{views,sessions,cache} \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ----------------------------------------
# 7. Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# ----------------------------------------
# 8. Build frontend (if needed)
RUN if [ -f package.json ]; then npm install && npm run build; fi

# ----------------------------------------
# 9. Laravel Artisan commands
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan migrate --force || true \
    && php artisan optimize:clear

# ----------------------------------------
# 10. Configure Nginx and Supervisor
RUN rm -f /etc/nginx/sites-enabled/default
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ----------------------------------------
# 11. Expose HTTP port
EXPOSE 80

# ----------------------------------------
# 12. Start all services
CMD ["/usr/bin/supervisord", "-n"]
