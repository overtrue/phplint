#!/usr/bin/env bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

BOX_MANIFEST_VERSION="4.4.0"
curl -Ls "https://github.com/llaville/box-manifest/releases/download/$BOX_MANIFEST_VERSION/box-manifest.phar" -o $SCRIPT_DIR/box-manifest
chmod +x $SCRIPT_DIR/box-manifest

$SCRIPT_DIR/box-manifest make build stub configure -r console-table.txt -r plain.txt -r sbom.json -r pkg.composer.txt -d $SCRIPT_DIR/.. --output-stub stub.php --output-conf box.json.dist -vvv --ansi
