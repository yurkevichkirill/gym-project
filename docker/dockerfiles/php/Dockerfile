# syntax=docker/dockerfile:1.7

FROM php:8.4-fpm-alpine AS base

COPY --from=ghcr.io/mlocati/php-extension-installer:2.11.12 \
    /usr/bin/install-php-extensions \
    /usr/local/bin/install-php-extensions

RUN apk add --no-cache su-exec && \
    install-php-extensions \
    gd \
    mbstring \
    pdo_pgsql \
    curl \
    xml \
    zip \
    bcmath \
    intl \
    opcache \
    amqp

WORKDIR /var/www

EXPOSE 9000

CMD ["php-fpm"]

FROM base AS prod-build

COPY --from=composer:2.10.1 /usr/bin/composer /usr/bin/composer

COPY symfony/composer.json symfony/composer.lock symfony/symfony.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-progress \
    --no-interaction \
    --no-scripts

COPY symfony/ ./

RUN composer dump-autoload \
        --no-dev \
        --classmap-authoritative \
        --no-interaction && \
    mkdir -p var/cache var/log && \
    chown -R www-data:www-data var

FROM base AS prod

ENV APP_ENV=prod \
    APP_DEBUG=0

COPY --from=prod-build --chown=www-data:www-data /var/www /var/www
COPY docker/dockerfiles/php/entrypoint.sh /usr/local/bin/app-entrypoint

RUN chmod 0755 /usr/local/bin/app-entrypoint

ENTRYPOINT ["app-entrypoint"]

FROM base AS dev

ARG INSTALL_XDEBUG=true
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
        install-php-extensions xdebug; \
        printf '%s\n' \
            'xdebug.start_with_request=trigger' \
            'xdebug.client_host=host.docker.internal' \
            'xdebug.client_port=9003' \
            > /usr/local/etc/php/conf.d/zz-xdebug.ini; \
    fi

ENV XDEBUG_MODE=off

COPY --from=composer:2.10.1 /usr/bin/composer /usr/bin/composer
