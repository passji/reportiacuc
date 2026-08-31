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
    && a2enmod ssl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# ใบรับรอง self-signed ต้องสร้างแยกบน server จริง (ไม่ commit private key เข้า git) — mount เป็น
# volume ทับ path นี้ตอน docker compose up (ดู docker-compose.yml: ./docker/php/ssl:/etc/apache2/ssl:ro)
RUN a2ensite default-ssl

WORKDIR /app
