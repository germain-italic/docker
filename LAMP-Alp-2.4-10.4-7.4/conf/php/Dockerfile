FROM php:7.4-fpm-alpine

COPY ./conf/php/php.ini-production /usr/local/etc/php/conf.d/php.ini

RUN docker-php-ext-install \
        pdo_mysql