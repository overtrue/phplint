#!/bin/sh

[ "$APP_DEBUG" == 'true' ] && set -x
set -e

if [ "$APP_DEBUG" == 'true' ]
then
  echo "> You will act as user: $(id -u -n)"
  echo "$(composer config --global --list)"
  /bin/sh -c "ls -l $(composer config --global home)"
fi

"$(composer config --global home)/vendor/bin/phplint" $@
