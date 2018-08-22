<?php

/*
 * Copyright 2018 Anthony Calabretta
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// Configuration wizard

$data = array(
	'baudRate'                    => 9600,
	'useUsb'                      => true,
	'useBluetooth'                => false,
	'bluetooth'                   =>
		array(),
	'defaultColor'                => 'FFFFFF',
	'saveDefaultColorToEEPROM'    => false,
	'useArtColorWhenPlayingMedia' => true,
	'animateArtColor'             => true,
	'minArtSaturation'            => 0.75,
	'minArtLuminance'             => 0.5,
	'idleMode'                    => 'color-cycle',
	'fadeSeconds'                 => 1,
	'artFadeSeconds'              => 2,
	'cycleFadeSeconds'            => 5,
	'cycleColors'                 =>
		array(
			'FF0000',
			'FFFF00',
			'00FF00',
			'00FFFF',
			'0000FF',
			'FF00FF',
		),
	'tcpPort'                     => 6969,
);

echo "Welcome to the RGBDuino configuration wizard!\n";
echo "Here we'll configure the basics of your RGBDuino installation!\n";

$in = input("Please set a default, fallback color, in hexadecimal notation. [FFFFFF (white)]");
if(check_hex($in)) $data["defaultColor"] = $in;

if(!empty(exec("which playerctl"))){
	$in = input(
		"Do you want to use Media Cover Art colors when you play music? [Y/n] [yes]"
	);
	if(in_array(strtolower($in), ["n", "no", "false", "0"])) $data["useArtColorWhenPlayingMedia"] = false;

	$in = input("Do you want to animate the Cover Art colors? [Y/n] [yes]");
	if(in_array(strtolower($in), ["n", "no", "false", "0"])) $data["animateArtColor"] = false;
}else{
	$data["useArtColorWhenPlayingMedia"] = false;
	$data["animateArtColor"] = false;
}

echo "Now choose a default mode
1 - Default color
2 - Cycle between colors (that you'll select later)
3 - Use wallpaper color\n";
$in = input("Choose [2]");
switch($in){
	case "1":
		$data["idleMode"] = "default-color";
		break;
	case "2":
		$data["idleMode"] = "color-cycle";
		break;
	case "3":
		$data["idleMode"] = "wallpaper";
		break;
	default:
		echo "Unrecognized option. Setted to 2 by default\n";
		break;
}

if($data["idleMode"] == "color-cycle"){
	$arrayColors = array();
	$shouldStop = false;
	echo "Please insert as much colors as you want, in hexadecimal notation.\n";
	echo "If you leave this section blank, it will default to the rainbow colors.\n";
	echo "When you are finished, please type 'stop'.\n";
	do{
		$in = input("Insert color #" . (count($arrayColors) + 1));
		if(check_hex($in)) $arrayColors[] = $in;
		else if($in == "stop") $shouldStop = true;
		else echo "Invalid color!\n";
	}while(!$shouldStop);

	if(!empty($arrayColors)) $data["cycleColors"] = $arrayColors;
}

$in = input("Will you use a USB connection for your Arduino? [Y/n] [yes]");
if(in_array(strtolower($in), ["n", "no", "false", "0"])) $data["useUsb"] = false;

// NOT YET, this is experimental
/*
$in = input("Will you use a Bluetooth connection for your Arduino? [Y/n] [no]");
if(in_array(strtolower($in), ["y", "yes", "true", "1"])) $data["useBluetooth"] = true;

if($data["useBluetooth"]){
	$bluetoothDevices = array();
	$shouldStop = false;
	echo "Please insert now your MAC address, an identifier for your Bluetooth Arduino and the preferred RFCOMM port.\n";
	do{
		$device = array();
		$in = input("MAC address" . (count($arrayColors) + 1));
		if(check_mac($in)) $device["mac"] = $in;
		else{
			echo "Wrong MAC address, retry!\n";
			continue;
		}

		$in = input("RFCOMM port (as integer)");
		if(!empty($in)) $device["rfcommPort"] = intval($in);
		else{
			echo "Wrong RFCOMM port, retry!\n";
			continue;
		}

		$in = input("Identifier" . (count($arrayColors) + 1));
		if(!empty($in)) $device["identifier"] = $in;
		else{
			echo "Empty identifier, retry!\n";
			continue;
		}

		$bluetoothDevices[] = $device;

		$in = input("Do you want to add another Bluetooth device? [Y/n]");
		if(in_array(strtolower($in), ["n", "no", "false", "0"])) $shouldStop = true;
	}while(!$shouldStop);

	if(!empty($bluetoothDevices)) $data["bluetooth"] = $bluetoothDevices;
}
*/

echo "You're all set now! Please, enjoy RGBDuino as much as you can!\n";

file_put_contents(
	"/home/" . exec("whoami") . "/.local/share/RGBDuino/config.json",
	json_encode($data, JSON_PRETTY_PRINT)
);


// FUNCTIONS
function input(string $str = "") : string{
	if(!empty($str)) echo $str . ": ";

	return trim(fgets(fopen('php://stdin', 'r')));
}

function check_hex(string $hex) : bool{
	$hex = strtoupper(substr(str_replace("#", "", trim($hex)), 0, 6));
	if(strlen($hex) !== 6 || preg_match("/^[0-9A-F]+$/", $hex) !== 1) return false;

	return true;
}

function check_mac(string $mac) : bool{
	$mac = strtoupper($mac);
	if(strlen($mac) !== 17) return false;
	if(preg_match("/^[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}$/g", $mac)
		!== 1) return false;

	return true;
}