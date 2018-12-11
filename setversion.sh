#!/bin/bash
# version changing script
NAME=Yamorphy
VERSION=0.1.1

FROM="$NAME v[0-9]{1,2}.[0-9]{1,2}.[0-9]{1,2}"
TO="$NAME v$VERSION"


sed -ri "s#$FROM#$TO#" *.php
sed -ri "s#$FROM#$TO#" README.md
echo $VERSION