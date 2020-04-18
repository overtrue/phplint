#!/bin/sh -l

set -xe

/root/.composer/vendor/bin/phplint ${INPUT_PATH} ${INPUT_OPTIONS}
