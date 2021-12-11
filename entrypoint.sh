#!/bin/sh -l

set -euxo pipefail

sh -c "/root/.composer/vendor/bin/phplint $*"