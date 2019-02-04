#!/bin/bash

# Release builder
# ---------------
# Required packages: jq, zip, sha1sum

CURRENT_VERSION=`jq -r '.version' ../src/app/code/PayAnyWay/PayAnyWay/composer.json`
FILE_NAME="payanyway-for-magento2-"$CURRENT_VERSION".zip"
FILE_NAME_SHA1=$FILE_NAME".sha1"
if [ ! -f "$FILE_NAME" ]; then
    cd ../src
    zip -r -9 ../arc/$FILE_NAME ./*
    cd ../arc
    sha1sum -b ./$FILE_NAME > $FILE_NAME_SHA1
else
    echo "Build file already exists, please change version number."
fi