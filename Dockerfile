ARG VERSION=7.4

FROM composer:1.10 AS build
RUN composer global require overtrue/phplint:^2.0

FROM php:${VERSION}-cli-alpine
COPY --from=build /tmp/vendor /root/.composer/vendor
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

ENTRYPOINT ["/entrypoint.sh"]
