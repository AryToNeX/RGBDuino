# RGBDuino [![ko-fi](https://www.ko-fi.com/img/donate_sm.png)](https://ko-fi.com/K3K3D0E0)
PHP daemon and CLI utility for DIY projects with Arduino boards and RGB
LED strips/bulbs

## Before you download

Please note: I am only challenging myself here.

Don't spam for ETAs or update requests.

If you like the idea you can always contribute to it (pull requests are VERY welcome),
fork it and maintain it, rewrite it in another language.

And finally, please note that when my interest in this project will be over, it will be
over. There's no way around it, sadly.

## Prerequisites

### The `dialout` group

The user who's using this project MUST be part of the `dialout` group.
```bash
$ sudo adduser $USER dialout
```

### `modemmanager` will screw up things

If you have `modemmanager` module for NetworkManager, either uninstall it or set
`udev` rules
so it will not randomly probe your Arduino serial ports.

#### Dealing with it with a USB connection

If you are using the USB connection, find your Arduino in `lsusb` command.
It will be something like `Bus XXX Device YYY: ID aaaa:bbbb Arduino`. We need the
`aaaa` and the `bbbb`.

Replace them in this string:

`ATTRS{idVendor}=="aaaa" ATTRS{idProduct}=="bbbb", ENV{ID_MM_DEVICE_IGNORE}="1"`

Now, take note of the modified string and go to the next section.

### Dealing with it with a Bluetooth connection

This is simpler than the USB connection, but unlike that one, this MUST be done
or you'll have to run the daemon as root (and it won't work properly).

Take note of every bluetooth virtual port that you are going to use with

```bash
$ sudo nano /etc/udev/rules.d/99-blacklist-mm.rules
```


### This project

This project relies on Playerctl, a CLI tool that can remotely control MPRIS-capable
players. You can get it
[here](https://github.com/acrisci/playerctl/releases/latest).

You will also need `notify-send`, `zenity`, `screen`, `pidof`, `kill`
and PHP (>= 7.1) with Sockets support.

## Using the project

### Using a packaged build (recommended)
```bash
$ wget -q -O- https://raw.githubusercontent.com/AryToNeX/RGBDuino/master/install.sh | bash -s -
```
### Build and install yourself
```bash
$ git clone https://github.com/AryToNeX/RGBDuino
$ cd RGBDuino
$ php BuildPhar.php # note: for this to work you MUST set phar.readonly=Off on your php.ini
$ mkdir ~/.local/share/RGBDuino
$ cp rgbduino ~/.local/share/RGBDuino/rgbduino
$ cp build/RGBDuino.phar ~/.local/share/RGBDuino/RGBDuino.phar

  # create shortcut to CLI tool (tested on Ubuntu 18.04, might differ on other distros)
$ echo "#!/bin/bash

php /home/$(whoami)/.local/share/RGBDuino/rgbduino \"\$@\"
" | tee "/home/$(whoami)/.local/bin/rgbduino" 1> /dev/null
```

## Used libraries

- **Color Extractor** from the PHP League
([repo](https://github.com/thephpleague/color-extractor))

## Apache 2.0 License

```
Copyright 2018 Anthony Calabretta

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
```
