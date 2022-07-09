ARG VERSION=8.1

FROM php:${VERSION}-cli-alpine AS build
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer global require overtrue/phplint

FROM php:${VERSION}-cli-alpine
COPY --from=build /root/.composer/vendor /root/.composer/vendor
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

WORKDIR /workdir
ENTRYPOINT ["/entrypoint.sh"]
