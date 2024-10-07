# syntax=docker/dockerfile:1.4
ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-cli-alpine

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

# Default container directory where to mount your project source files
# GitLab uses $CI_PROJECT_DIR to identify where job runs on source files
# GitHub uses $GITHUB_WORKSPACE to identify where job runs on source files
RUN mkdir /workdir

# Create a group and user
RUN addgroup appgroup && adduser appuser -D -G appgroup

# Tell docker that all future commands should run as the appuser user
USER appuser

# Install Composer v2 then overtrue/phplint package
COPY --from=composer/composer:2-bin /composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer global require --no-progress overtrue/phplint 9.5.x-dev

# Following recommendation at https://docs.github.com/en/actions/creating-actions/dockerfile-support-for-github-actions#workdir

ENTRYPOINT ["/entrypoint.sh"]
