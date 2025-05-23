FROM php:8.2-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo_mysql mbstring zip gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN php artisan config:clear && php artisan route:clear && php artisan view:clear \
    && php artisan config:cache && php artisan route:cache && php artisan view:cache

RUN chmod -R 775 storage bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache

# Railway sẽ inject PORT, ta đọc từ biến môi trường
ENV PORT=8000
EXPOSE $PORT
CMD php artisan serve --host=0.0.0.0 --port=$PORT
