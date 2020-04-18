#!/bin/sh -l

set -xe

export

phplint ${INPUT_PATH} ${INPUT_OPTIONS}
