FROM php:8.3-cli
RUN apt-get update && apt-get install -y git unzip libicu-dev libpq-dev libzip-dev && docker-php-ext-install intl pdo_pgsql zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
RUN composer install --no-interaction --prefer-dist
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
