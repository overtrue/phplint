ARG VERSION=7.3

FROM composer:1.10 AS build
RUN composer global require overtrue/phplint

FROM php:${VERSION}-cli
COPY --from=build /tmp/vendor /root/.composer/vendor

COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
