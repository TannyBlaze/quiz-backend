FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    unzip curl git libzip-dev zip libssl-dev pkg-config \
    && docker-php-ext-install zip

RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 10000

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]