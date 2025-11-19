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

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

FROM --platform=$BUILDPLATFORM php AS vendor

WORKDIR /app

USER www-data

COPY --chown=www-data:www-data composer.* /app/
COPY --chown=www-data:www-data symfony.lock /app/

COPY --chown=www-data:www-data ecs.php /app/
COPY --chown=www-data:www-data phpstan.neon /app/
COPY --chown=www-data:www-data phpstan-baseline.neon /app/
COPY --chown=www-data:www-data phpunit.dist.xml /app/

COPY --chown=www-data:www-data migrations /app/migrations
COPY --chown=www-data:www-data bin /app/bin/
COPY --chown=www-data:www-data public /app/public
COPY --chown=www-data:www-data ecs.php /app/
COPY --chown=www-data:www-data .env /app/
COPY --chown=www-data:www-data config /app/config
COPY --chown=www-data:www-data tests /app/tests
COPY --chown=www-data:www-data templates /app/templates
COPY --chown=www-data:www-data src /app/src

RUN composer install --optimize-autoloader --no-scripts

EXPOSE 80

FROM --platform=$BUILDPLATFORM php AS test

USER www-data
WORKDIR /app

COPY --chown=www-data:www-data --from=vendor /app /app/

COPY --chown=www-data:www-data .env.test /app/

FROM --platform=$BUILDPLATFORM php AS dev

WORKDIR /app

USER root

FROM --platform=$BUILDPLATFORM php AS production

USER www-data
WORKDIR /app

COPY --chown=www-data:www-data --from=vendor /app /app/

COPY docker/php/ext-opcache.ini /etc/php84/conf.d/00_opcache.ini

RUN composer install --no-dev --no-scripts --no-plugins --prefer-dist --no-progress --no-interaction
RUN composer dump-autoload --optimize && \
    composer check-platform-reqs
