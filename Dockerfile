# Dựa trên PHP 8 với Apache
FROM php:8.2-apache

# Cài các extension PHP cần thiết
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Cài Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Sao chép project vào container
COPY . /var/www/html

# Cài đặt thư viện Laravel
RUN composer install --no-dev --optimize-autoloader

# Set quyền cho Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Tạo APP_KEY
RUN php artisan key:generate

# Mở cổng cho Apache
EXPOSE 80
