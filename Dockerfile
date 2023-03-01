FROM php:7.1-cli-alpine3.10

RUN apk update \
    && apk upgrade \
    && apk add --no-cache $PHPIZE_DEPS bash git libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/cache/apk/*

COPY --from=composer/composer:2.2-bin /composer /usr/bin/composer

WORKDIR /var/www/wordpress-diffy
