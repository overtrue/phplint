ARG VERSION=7.3
FROM php:${VERSION}-cli

RUN set -xe \
    && apt-get update \
    && apt-get install -y git unzip \
    && rm -rf /var/lib/apt/lists/* \
    # Install composer
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir /usr/local/bin --filename composer \
    && php -r "unlink('composer-setup.php');" \
    # Copy default php.ini
    && cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini \
    && composer global require overtrue/phplint

COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
