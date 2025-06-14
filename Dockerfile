# syntax = docker/dockerfile:experimental

ARG PHP_VERSION=8.2
ARG NODE_VERSION=18
FROM fideloper/fly-laravel:${PHP_VERSION} as base

ARG PHP_VERSION

LABEL fly_launch_runtime="laravel"

# Install supervisor
RUN apt-get update && apt-get install -y supervisor

# Copy application code, skipping files based on .dockerignore
COPY . /var/www/html

# Create and set permissions for required directories
RUN mkdir -p /var/lib/nginx/body /var/run \
    && chown -R www-data:www-data /var/lib/nginx /var/run

# Ensure Nginx listens on the correct port (PORT or 8080 as fallback)
RUN sed -i 's/listen 80;/listen ${PORT:-8080};/g' /etc/nginx/sites-enabled/default

RUN composer install --optimize-autoloader --no-dev \
    && mkdir -p storage/logs \
    && php artisan optimize:clear \
    && chown -R www-data:www-data /var/www/html \
    && echo "MAILTO=\"\"\n* * * * * www-data /usr/bin/php /var/www/html/artisan schedule:run" > /etc/cron.d/laravel \
    && cp .fly/entrypoint.sh /entrypoint \
    && chmod +x /entrypoint

# Copy supervisord configuration
COPY .fly/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Laravel 11 proxy trust configuration
RUN if php artisan --version | grep -q "Laravel Framework 1[1-9]"; then \
    sed -i='' '/->withMiddleware(function (Middleware \$middleware) {/a\
    \$middleware->trustProxies(at: "*");\
    ' bootstrap/app.php; \
    else \
    sed -i 's/protected \$proxies/protected \$proxies = "*"/g' app/Http/Middleware/TrustProxies.php; \
    fi

# Octane configuration
RUN if grep -Fq "laravel/octane" /var/www/html/composer.json; then \
    rm -rf /etc/supervisor/conf.d/fpm.conf; \
    if grep -Fq "spiral/roadrunner" /var/www/html/composer.json; then \
    mv /etc/supervisor/octane-rr.conf /etc/supervisor/conf.d/octane-rr.conf; \
    if [ -f ./vendor/bin/rr ]; then ./vendor/bin/rr get-binary; fi; \
    rm -f .rr.yaml; \
    else \
    mv .fly/octane-swoole /etc/services.d/octane; \
    mv /etc/supervisor/octane-swoole.conf /etc/supervisor/conf.d/octane-swoole.conf; \
    fi; \
    rm /etc/nginx/sites-enabled/default; \
    ln -sf /etc/nginx/sites-available/default-octane /etc/nginx/sites-enabled/default; \
    fi

# Multi-stage build: Build static assets
FROM node:${NODE_VERSION} as node_modules_go_brrr

RUN mkdir /app
RUN mkdir -p /app
WORKDIR /app
COPY . .
COPY --from=base /var/www/html/vendor /app/vendor

RUN if [ -f "vite.config.js" ]; then \
    ASSET_CMD="build"; \
    else \
    ASSET_CMD="production"; \
    fi; \
    if [ -f "yarn.lock" ]; then \
    yarn install --frozen-lockfile; \
    yarn $ASSET_CMD; \
    elif [ -f "pnpm-lock.yaml" ]; then \
    corepack enable && corepack prepare pnpm@latest-8 --activate; \
    pnpm install --frozen-lockfile; \
    pnpm run $ASSET_CMD; \
    elif [ -f "package-lock.json" ]; then \
    npm ci --no-audit; \
    npm run $ASSET_CMD; \
    else \
    npm install; \
    npm run $ASSET_CMD; \
    fi;

FROM base

COPY --from=node_modules_go_brrr /app/public /var/www/html/public-npm
RUN rsync -ar /var/www/html/public-npm/ /var/www/html/public/ \
    && rm -rf /var/www/html/public-npm \
    && chown -R www-data:www-data /var/www/html/public

EXPOSE 8080 6001

ENTRYPOINT ["/entrypoint"]