#!/bin/sh

[ "$APP_DEBUG" == 'true' ] && set -x
set -e

if [ "$APP_DEBUG" == 'true' ]
then
  echo "> You will act as user: $(id -u -n)"
  composer_global_home="/home/$(id -u -n)/.composer"
  echo "> Path to Composer home dir: ${composer_global_home}"
fi

"${composer_global_home}/vendor/bin/phplint" $@
