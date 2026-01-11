FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    linux-headers \
    libpq-dev \
    curl-dev \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    mysql-client \
    postgresql-dev

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions \
        gd \
        mbstring \
        mysqli \
        pdo_mysql \
        pdo_pgsql \
        curl \
        xml \
        zip \
        bcmath \
        intl \
        opcache

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

EXPOSE 9000

CMD ["php-fpm"]