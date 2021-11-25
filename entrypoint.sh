#!/bin/sh -l

set -e

exec /root/.composer/vendor/bin/phplint "$@"
