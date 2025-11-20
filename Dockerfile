FROM --platform=$BUILDPLATFORM php:8.4-fpm AS php

USER root

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install zip pdo pdo_pgsql pgsql bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

FROM --platform=$BUILDPLATFORM php AS vendor

WORKDIR /app

COPY composer.* /app/
COPY symfony.lock /app/

COPY ecs.php /app/
COPY phpstan.neon /app/
COPY phpstan-baseline.neon /app/
COPY phpunit.dist.xml /app/

COPY migrations /app/migrations
COPY bin /app/bin/
COPY public /app/public
COPY ecs.php /app/
COPY .env /app/
COPY config /app/config
COPY tests /app/tests
COPY templates /app/templates
COPY src /app/src

RUN composer install --optimize-autoloader --no-scripts

RUN chown -R www-data:www-data /app && \
    mkdir -p /app/var/cache /app/var/log && \
    chmod -R 777 /app/var && \
    touch /app/.phpunit.result.cache && \
    chmod 666 /app/.phpunit.result.cache

EXPOSE 80

FROM --platform=$BUILDPLATFORM php AS test

USER root

# Copy Xdebug configuration for test environment
COPY docker/php/ext-xdebug.ini /usr/local/etc/php/conf.d/50_xdebug.ini

USER www-data
WORKDIR /app

COPY --chown=www-data:www-data --from=vendor /app /app/

COPY --chown=www-data:www-data .env.test /app/

FROM --platform=$BUILDPLATFORM php AS dev

USER root

# Copy Xdebug configuration for dev environment
COPY docker/php/ext-xdebug.ini /usr/local/etc/php/conf.d/50_xdebug.ini

USER www-data
WORKDIR /app

COPY --chown=www-data:www-data --from=vendor /app /app/

COPY --chown=www-data:www-data .env.dev /app/

FROM --platform=$BUILDPLATFORM php AS production

USER www-data
WORKDIR /app

COPY --chown=www-data:www-data --from=vendor /app /app/

COPY docker/php/ext-opcache.ini /etc/php84/conf.d/00_opcache.ini

RUN composer install --no-dev --no-scripts --no-plugins --prefer-dist --no-progress --no-interaction
RUN composer dump-autoload --optimize && \
    composer check-platform-reqs
