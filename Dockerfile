ARG VERSION=7.4

FROM composer:2.1
FROM php:${VERSION}-cli-alpine as build

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer global require overtrue/phplint:^3.0.0
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

WORKDIR /workdir
ENTRYPOINT ["/entrypoint.sh"]
