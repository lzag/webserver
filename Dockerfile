FROM php:8.4-cli

RUN docker-php-ext-install sockets
RUN apt-get update && apt-get install -y netcat-traditional
