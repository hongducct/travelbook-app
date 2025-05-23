FROM php:8.2-fpm

# Cài đặt thư viện cần thiết
RUN apt-get update && apt-get install -y \
    nginx git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo_mysql mbstring zip gd

# Cài Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Tạo thư mục làm việc
WORKDIR /var/www

# Copy mã nguồn vào container
COPY . .

# Cài đặt dependencies
RUN composer install --no-dev --optimize-autoloader

# Cấp quyền
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Copy file cấu hình nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Start nginx + php-fpm
CMD service nginx start && php-fpm
