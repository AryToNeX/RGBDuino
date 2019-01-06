#!/usr/bin/env bash

echo "Pulling RGBDuino's latest version to current directory"

BUILDNUM=$(wget -q -O- http://tony0000.altervista.org/RGBDuino/currentbuild.txt)

echo "Pulling version $BUILDNUM"

mkdir -p "build"
wget -q "http://tony0000.altervista.org/RGBDuino/builds/$BUILDNUM/RGBDuino-Server.phar" -O "build/RGBDuino-Server.phar"
wget -q "http://tony0000.altervista.org/RGBDuino/builds/$BUILDNUM/RGBDuino-Client.phar" -O "build/RGBDuino-Client.phar"
wget -q "http://tony0000.altervista.org/RGBDuino/builds/$BUILDNUM/RGBDuino-CLI.phar" -O "build/RGBDuino-CLI.phar"
echo ${BUILDNUM} | tee "build/current-build" 1> /dev/null

echo "#!/bin/bash

php /home/$(whoami)/.local/share/RGBDuino-CLI/RGBDuino-CLI.phar \"\$@\"
" | tee "build/rgbcli" 1> /dev/null

chmod +x "build/rgbcli"

echo "Pulled correctly!"
