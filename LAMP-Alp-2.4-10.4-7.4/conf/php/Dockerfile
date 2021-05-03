FROM php:7.4-fpm-alpine

COPY ./conf/php/php.ini-production /usr/local/etc/php/conf.d/php.ini

ARG APK_ADD
RUN apk add --no-cache zip $APK_ADD

ARG PHP_EXTENSIONS_CONFIGURE
RUN docker-php-ext-configure zip $PHP_EXTENSIONS

ARG PHP_EXTENSIONS_INSTALL
RUN docker-php-ext-install zip $PHP_EXTENSIONS_INSTALL