#!/usr/bin/env bash

echo "Installing RGBDuino"

BUILDNUM=$(wget -q -O- http://tony0000.altervista.org/RGBDuino/currentbuild.txt)

echo "Pulling version $BUILDNUM"

mkdir -p "/home/$(whoami)/.local/share/RGBDuino"
wget -q "http://tony0000.altervista.org/RGBDuino/builds/$BUILDNUM/RGBDuino.phar" -O "/home/$(whoami)/.local/share/RGBDuino/RGBDuino.phar"
wget -q "http://tony0000.altervista.org/RGBDuino/builds/$BUILDNUM/rgbduino" -O "/home/$(whoami)/.local/share/RGBDuino/rgbduino"
wget -q "https://raw.githubusercontent.com/AryToNeX/RGBDuino/master/wizard.php" -O "/home/$(whoami)/.local/share/RGBDuino/wizard.php"
echo ${BUILDNUM} | tee "/home/$(whoami)/.local/share/RGBDuino/current-build" 1> /dev/null

mkdir -p "/home/$(whoami)/.local/bin"
echo "#!/bin/bash

php /home/$(whoami)/.local/share/RGBDuino/rgbduino \"\$@\"
" | tee "/home/$(whoami)/.local/bin/rgbduino" 1> /dev/null

php "/home/$(whoami)/.local/share/RGBDuino/wizard.php"

echo "Installed correctly!"
