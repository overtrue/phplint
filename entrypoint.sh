#!/bin/sh -l

set -euxo pipefail

exec /root/.composer/vendor/bin/phplint $@
