# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# base: PHP 8.3-FPM + nginx runtime, and the extensions this app actually
# requires (composer.lock's ext-* requires, plus intl and pdo_pgsql). Shared
# by every stage below so dev and production never drift from each other.
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache nginx postgresql-libs postgresql16-client icu-libs oniguruma \
    && apk add --no-cache --virtual .build-deps postgresql-dev icu-dev oniguruma-dev \
    && docker-php-ext-install pdo_pgsql pgsql intl mbstring bcmath opcache \
    && apk del .build-deps

WORKDIR /var/www/html

COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# ---------------------------------------------------------------------------
# dev: PHP + Composer + dev dependencies, for local work and for verifying
# this Dockerfile itself. Source is bind-mounted by docker-compose, not
# copied in, so edits on the host are picked up live.
# ---------------------------------------------------------------------------
FROM base AS dev

RUN apk add --no-cache git unzip bash
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/entrypoint-dev.sh /usr/local/bin/entrypoint-dev.sh
RUN chmod +x /usr/local/bin/entrypoint-dev.sh

ENTRYPOINT ["entrypoint-dev.sh"]

# ---------------------------------------------------------------------------
# vendor: composer install --no-dev in isolation, so composer/git never
# reach the production image.
# ---------------------------------------------------------------------------
FROM base AS vendor

RUN apk add --no-cache git unzip
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./
COPY app app
COPY bootstrap bootstrap
COPY config config
COPY database database
COPY routes routes

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# ---------------------------------------------------------------------------
# production: nginx + php-fpm serving the committed, already-built assets.
# ---------------------------------------------------------------------------
FROM base AS production

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/opcache-production.ini /usr/local/etc/php/conf.d/opcache-production.ini

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache \
    && mkdir -p /run/nginx

VOLUME ["/var/www/html/storage/app"]

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=20s \
    CMD wget -qO- http://127.0.0.1/up || exit 1

ENTRYPOINT ["entrypoint.sh"]
