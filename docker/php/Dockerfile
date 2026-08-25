FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        libonig-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring intl zip gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app
