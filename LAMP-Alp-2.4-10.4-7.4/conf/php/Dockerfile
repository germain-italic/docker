FROM php:7.4-fpm-alpine

COPY ./conf/php/php.ini-production /usr/local/etc/php/conf.d/php.ini

ARG APK_ADD
RUN apk add --no-cache zip $APK_ADD

WORKDIR /var/www/html

ARG PHP_EXTENSIONS_INSTALL
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
install-php-extensions $PHP_EXTENSIONS_INSTALL