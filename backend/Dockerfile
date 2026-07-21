FROM php:8.4-cli-alpine

RUN apk add --no-cache sqlite sqlite-dev libzip-dev \
    && docker-php-ext-install pdo_sqlite bcmath > /dev/null

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
# Dev dependencies stay in: the demo seeder uses Faker.
RUN composer install --no-scripts --no-autoloader --no-interaction --quiet

COPY . .
RUN composer dump-autoload --optimize --quiet

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
