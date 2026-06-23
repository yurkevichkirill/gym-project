FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    linux-headers \
    libpq-dev \
    curl-dev \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    postgresql-dev

COPY --from=ghcr.io/mlocati/php-extension-installer:2.11.12 \
    /usr/bin/install-php-extensions \
    /usr/local/bin/install-php-extensions

RUN install-php-extensions \
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

WORKDIR /var/www

EXPOSE 9000

CMD ["php-fpm"]
