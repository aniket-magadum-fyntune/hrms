# syntax=docker/dockerfile:1

FROM php:8.4-cli-bookworm AS php-build
WORKDIR /app
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        git \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

FROM php-build AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM php-build AS frontend
WORKDIR /app
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=vendor /app/vendor ./vendor
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY resources ./resources
COPY public ./public
COPY artisan composer.json composer.lock vite.config.ts tsconfig.json components.json ./
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && php artisan package:discover --ansi \
    && php artisan wayfinder:generate --with-form \
    && npm run build

FROM php:8.4-fpm-bookworm AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        nginx \
        unzip \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -f /etc/nginx/sites-enabled/default \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build
COPY docker/entrypoint.sh /usr/local/bin/hrms-entrypoint
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

RUN chmod +x /usr/local/bin/hrms-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && mkdir -p /run/nginx \
    && chown -R www-data:www-data storage bootstrap/cache /run/nginx \
    && php artisan package:discover --ansi

EXPOSE 80

ENTRYPOINT ["hrms-entrypoint"]
CMD ["nginx", "-g", "daemon off;"]
