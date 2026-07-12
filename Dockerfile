# syntax=docker/dockerfile:1

# ---- Base: PHP FPM with all shared extensions ----
# Using fpm-bookworm so the runtime stage can extend this directly,
# avoiding recompiling the same extensions twice.
FROM php:8.4-fpm-bookworm AS php-build
WORKDIR /app
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update \
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
        zip

# ---- Composer dependencies ----
FROM php-build AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# ---- Node.js dependencies (cached independently of PHP tooling) ----
FROM node:22-bookworm-slim AS node-deps
WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci

# ---- Frontend build ----
FROM php-build AS frontend
WORKDIR /app
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
COPY --from=node-deps /app/node_modules ./node_modules
COPY --from=vendor /app/vendor ./vendor
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY resources ./resources
COPY public ./public
COPY artisan composer.json composer.lock vite.config.ts tsconfig.json components.json package.json ./
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && php artisan package:discover --ansi \
    && php artisan wayfinder:generate --with-form \
    && npm run build

# ---- Runtime: extends php-build to avoid recompiling shared extensions ----
FROM php-build AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false

RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
    && docker-php-ext-install opcache \
    && pecl install redis-6.1.0 \
    && docker-php-ext-enable redis \
    && rm -f /etc/nginx/sites-enabled/default

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
