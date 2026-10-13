FROM php:8.3-cli-alpine AS base

RUN apk add --no-cache \
    bash \
    curl \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    sqlite \
    sqlite-dev \
    unzip \
    zip \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
        pcntl \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS dev
COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist
COPY . .
RUN sed -i 's/\r$//' /var/www/html/docker/backend-entrypoint.sh \
    && chmod +x /var/www/html/docker/backend-entrypoint.sh \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        database/sqlite \
    && composer dump-autoload
EXPOSE 8000
ENTRYPOINT ["/var/www/html/docker/backend-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

FROM base AS runner
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN sed -i 's/\r$//' /var/www/html/docker/backend-entrypoint.sh \
    && chmod +x /var/www/html/docker/backend-entrypoint.sh \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        database/sqlite \
    && composer dump-autoload --optimize

EXPOSE 8000

ENTRYPOINT ["/var/www/html/docker/backend-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
