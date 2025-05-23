FROM php:8.2

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo_mysql mbstring zip gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader \
    && php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

RUN chmod -R 775 storage bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache

ENV PORT=8080
EXPOSE $PORT

CMD php -d variables_order=EGPCS -S 0.0.0.0:$PORT -t public
