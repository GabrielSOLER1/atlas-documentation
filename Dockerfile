FROM node:22-alpine AS frontend
WORKDIR /build
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

FROM composer:2 AS dependencies
WORKDIR /app
COPY backend/composer.* ./
RUN composer install --no-dev --no-interaction --prefer-dist --ignore-platform-req=ext-pdo_pgsql --ignore-platform-req=ext-gd

FROM php:8.4-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev openssl libpng-dev libjpeg62-turbo-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_pgsql gd \
    && a2enmod ssl headers rewrite deflate \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY backend/ ./
COPY --from=dependencies /app/vendor/ ./vendor/
COPY --from=frontend /backend/public/build/ ./public/build/
COPY docker/apache-app.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/atlas.ini
RUN mkdir -p var/cache var/log && chown -R www-data:www-data var
CMD ["sh", "-c", "su -s /bin/sh www-data -c 'php bin/console app:init' && exec apache2-foreground"]
