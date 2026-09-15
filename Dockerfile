# syntax=docker/dockerfile:1.7

FROM node:22-bookworm-slim AS frontend
WORKDIR /build
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.4-fpm-bookworm AS php-base
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY docker/php/php.ini /usr/local/etc/php/conf.d/autoestimate.ini
COPY docker/php/entrypoint.sh /usr/local/bin/autoestimate-entrypoint
RUN chmod +x /usr/local/bin/autoestimate-entrypoint

FROM php-base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
RUN rm -f bootstrap/cache/*.php \
    && composer install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --optimize-autoloader

FROM php-base AS app
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=vendor --chown=www-data:www-data /var/www/html/bootstrap/cache ./bootstrap/cache
COPY --from=frontend --chown=www-data:www-data /build/public/build ./public/build

RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["autoestimate-entrypoint"]
CMD ["php-fpm"]

FROM nginx:stable-alpine AS nginx
WORKDIR /var/www/html
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public ./public
COPY --from=frontend /build/public/build ./public/build
EXPOSE 80
