#!/bin/bash

echo "Removing ALL of RGBDuino"
rm -r ~/.cache/RGBDuino/
rm -r ~/.local/share/RGBDuino-Server/
rm -r ~/.local/share/RGBDuino-Client/
rm -r ~/.local/share/RGBDuino-CLI/
rm -r ~/.local/bin/rgbcli
echo "RGBDuino removed"