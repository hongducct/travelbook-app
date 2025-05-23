FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Cài đặt các gói cần thiết
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo_mysql mbstring zip gd

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy toàn bộ mã nguồn vào container
COPY . .

# Cài đặt các gói Composer ở chế độ production
RUN composer install --no-dev --optimize-autoloader

# Clear & Cache config
RUN php artisan config:clear && php artisan route:clear && php artisan view:clear \
    && php artisan config:cache && php artisan route:cache && php artisan view:cache

# Phân quyền thư mục
RUN chmod -R 775 storage bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache

# Expose port & chạy Laravel
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
