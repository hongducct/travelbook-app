FROM php:8.2-fpm

# Cài các package cần thiết
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip gd

# Cài composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy source code
COPY . .

# Cài các thư viện PHP qua composer
RUN composer install --no-dev --optimize-autoloader

# Tạo cache để Laravel chạy nhanh hơn
RUN php artisan config:clear && php artisan route:clear && php artisan view:clear \
    && php artisan config:cache && php artisan route:cache && php artisan view:cache

# Mở port 8000
EXPOSE 8000

# Start Laravel bằng built-in server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
