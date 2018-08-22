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

Take note of every RFCOMM port that you'll use for your Arduino boards connected via
Bluetooth. It's better if you hardcode them in the config file (since it can be done).

For example, you have in your config file this situation:
```json
{
  "useBluetooth": true,
  "bluetooth": [
    {
      "mac": "XX:XX:XX:YY:YY:YY",
      "identifier": "KitchenLights",
      "rfcommPort": 0
    },
    {
      "mac": "AA:AA:AA:BB:BB:BB",
      "identifier": "LivingRoomLights",
      "rfcommPort": 1
    }
  ]
}
```

In this case, take note of your `rfcommPort` values, `0` and `1`, and then write down
these lines
```
KERNEL=="rfcomm0", ENV{ID_MM_DEVICE_IGNORE}="1"
KERNEL=="rfcomm1", ENV{ID_MM_DEVICE_IGNORE}="1"
```

Note that the `0` and `1` are situated next to the `KERNEL=="rfcomm` part and right
before the closing `"`.

**Shortcut:** if you just don't care about using Bluetooth as broadband connection via
`modemmanager` you can use a wildcard in the `udev` rule like so:

```
KERNEL=="rfcomm*", ENV{ID_MM_DEVICE_IGNORE}="1"
```

This will match ALL RFCOMM virtual ports and `modemmanager` will not probe them at all.

### Setting up the `udev` rules

This is fairly simple. All you should do is open a text editor like `nano` as root
and create a file named `/etc/udev/rules.d/99-blacklist-mm.rules`; then put the
strings you need there and save it.

Now it's time to reload `udev`'s rules. In a terminal, run:
```bash
$ sudo udevadm control --reload-rules
```

And you should be all set (at least for now).

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

## Configuration file

```json
{
  // The default color that will be displayed
  // if idleMode is set to defaultColor or if you screw up things
  "defaultColor": "FFFFFF",
  
  // This boolean saves the default color to EEPROM
  // at every restart of the daemon. Use it carefully,
  // it can be useful if you want only one color and you
  // don't necessarily plug your Arduino to your PC everytime
  "saveDefaultColorToEEPROM": false,
  
  // This is the main switch for the Media Cover Art color display.
  // It requires Playerctl to be installed on your system.
  "useArtColorWhenPlayingMedia": true,
  
  // This is the switch for the Media Cover Art animation.
  // It consists in gradually changing colors which are in a palette
  // generated against the Media Cover Art.
  "animateArtColor": true,
  
  // Float value that goes from 0 to 1.
  // This value regulates the minimum saturation for the Media Cover
  // Art colors.
  // Changing this will result in either white-ish or more colorful colors.
  "minArtSaturation": 0.75,
  
  // Float value that goes from 0 to 1.
  // This value regulates the minimum luminance for the Media Cover
  // Art colors.
  // Changing this will result in either darker or brighter colors.
  "minArtLuminance": 0.50,
  
  // What to do when there's no music playing
  // Possible values are: default-color, color-cycle, wallpaper.
  "idleMode": "color-cycle",
  
  // Float value that goes from 0.002 to infinite (in seconds).
  // How much should it take to fade to a chosen color or the wallpaper color?
  // Note: it will not precisely take the specified amount of seconds
  // unless you have a super-powered computer which can do maths literally instantly.
  // Change this according to your needs and bring a chronometer to figure out how
  // really slow it is.
  "fadeSeconds": 2,
  
  // Float value that goes from 0.002 to infinite (in seconds).
  // How much should it take to fade between colors in the Media Cover Art?
  // Note: it will not precisely take the specified amount of seconds
  // unless you have a super-powered computer which can do maths literally instantly.
  // Change this according to your needs and bring a chronometer to figure out how
  // really slow it is.
  "artFadeSeconds": 5,
  
  // Float value that goes from 0.002 to infinite (in seconds).
  // How much should it take to fade between the colors in the idle color cycle mode?
  // Note: it will not precisely take the specified amount of seconds
  // unless you have a super-powered computer which can do maths literally instantly.
  // Change this according to your needs and bring a chronometer to figure out how
  // really slow it is.
  "cycleFadeSeconds": 10,
  
  // Array of HEX colors (as strings)
  // Specify here which colors you prefer when cycling in idle mode.
  // Defaults to the rainbow.
  "cycleColors": [
    "FF0000",
    "FFFF00",
    "00FF00",
    "00FFFF",
    "0000FF",
    "FF00FF"
  ],
  
  // Integer value, the TCP port the daemon will be listening on.
  // It's needed for the CLI tool to work and it can be useful for you, if you
  // want to connect to the daemon from the LAN to change colors and do stuff.
  "tcpPort": 6969,
  
  // Do you want to use USB connection for your Arduino boards?
  "useUsb": true,
  
  // Do you want to use Bluetooth connection for your Arduino boards?
  "useBluetooth": false,
  
  // Array, list of your Bluetooth Arduino boards
  "bluetooth": [
    {
      // the MAC address of your Bluetooth component
      "mac": "XX:XX:XX:YY:YY:YY",
      
      // an identifier to remember which board is which,
      // both here in the config file and there in the program itself
      "identifier": "KitchenLights",
      
      // the RFCOMM port that will be used for this board
      // Pro tip: choose a port that you don't already use for other things
      "rfcommPort": 0
    }
  ]
}
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
