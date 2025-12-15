FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    acl \
    fcgi \
    file \
    gettext \
    git \
    gnu-libiconv \
    bash \
    icu-dev \
    libzip-dev \
    zip \
    linux-headers

RUN docker-php-ext-install intl zip pdo pdo_mysql opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]
