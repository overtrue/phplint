#!/bin/sh -l

[ "$APP_DEBUG" == 'true' ] && set -x
set -e

if [ ! -z "$INPUT_PATH" ]; then
  /root/.composer/vendor/bin/phplint $INPUT_PATH $INPUT_OPTIONS
else
  sh -c "/root/.composer/vendor/bin/phplint $*"
fi