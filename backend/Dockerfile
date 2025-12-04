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

COPY backend/composer.* /app/
COPY backend/symfony.lock /app/

COPY backend/ecs.php /app/
COPY backend/phpstan.neon /app/
COPY backend/phpstan-baseline.neon /app/
COPY backend/phpunit.dist.xml /app/

COPY backend/migrations /app/migrations
COPY backend/bin /app/bin/
COPY backend/public /app/public
COPY backend/.env /app/
COPY backend/config /app/config
COPY backend/tests /app/tests
COPY backend/templates /app/templates
COPY backend/translations /app/translations
COPY backend/src /app/src

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
COPY backend/docker/php/ext-xdebug.ini /usr/local/etc/php/conf.d/50_xdebug.ini

# Allow www-data to match host user permissions
ARG USER_ID=1000
ARG GROUP_ID=1000
RUN usermod -u ${USER_ID} www-data && \
    groupmod -g ${GROUP_ID} www-data

USER www-data
WORKDIR /app

COPY --chown=www-data:www-data --from=vendor /app /app/

COPY --chown=www-data:www-data backend/.env.test /app/

FROM --platform=$BUILDPLATFORM php AS dev

USER root

# Copy Xdebug configuration for dev environment
COPY backend/docker/php/ext-xdebug.ini /usr/local/etc/php/conf.d/50_xdebug.ini

# Allow www-data to match host user permissions
ARG USER_ID=1000
ARG GROUP_ID=1000
RUN usermod -u ${USER_ID} www-data && \
    groupmod -g ${GROUP_ID} www-data

USER www-data
WORKDIR /app

COPY --chown=www-data:www-data --from=vendor /app /app/

COPY --chown=www-data:www-data backend/.env.dev /app/

FROM --platform=$BUILDPLATFORM php AS production

USER root

# Copy opcache configuration for production
COPY backend/docker/php/ext-opcache.ini /usr/local/etc/php/conf.d/10_opcache.ini

USER www-data
WORKDIR /app

COPY --chown=www-data:www-data --from=vendor /app /app/

RUN composer install --no-dev --no-scripts --no-plugins --prefer-dist --no-progress --no-interaction
RUN composer dump-autoload --optimize && \
    composer check-platform-reqs
