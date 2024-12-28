FROM php:8.4-zts

RUN docker-php-ext-install sockets
RUN apt-get update && apt-get install -y \
    netcat-traditional
RUN pecl install parallel \
    && docker-php-ext-enable parallel
