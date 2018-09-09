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

namespace AryToNeX\RGBDuino\server;

cli_set_process_title("rgbduino-server");

$status = new Status();
$discovery = new DeviceDiscovery($status);

echo "Initializing device connections...\n";
$tries = 0;
do{
	$good = true;
	$discovery->checkConnected();
	//$status->getDevicePool()->add("FakeArduino", new devices\FakeArduino());
	// DEBUGGING FTW
	if(empty($status->getDevicePool()->toArray())){
		echo "No devices connected! Waiting 2 seconds...\n";
		$tries++;
		$good = false;
		sleep(2);
	}
}while(!$good && $tries < 5);

if(!$good){
	echo "Can't connect. Exiting...\n";
	exit(-1);
}

unset($good);
unset($tries);

// Started successfully!
echo "Device connections established correctly!\n";

$fader = new FaderHelper($status);

pcntl_async_signals(true);
pcntl_signal(
	SIGINT,
	function() use ($status){
		$status->setShouldExit(1);
	}
);

// start with default color
foreach($status->getDevicePool()->toArray() as $device)
	$device->sendColor(Color::fromHex($status->getConfig()->getValue("defaultColor") ?? "FFFFFF"));

// save to EEPROM if config says yes
if($status->getConfig()->getValue("saveDefaultColor") ?? false){
	usleep(30000); // we need to keep calm and let the Device display the color first
	foreach($status->getDevicePool()->toArray() as $device)
		$device->saveDisplayedColor();
	usleep(30000); // then we need to relax a little more to let the Device save the color
}

$albumArtMediaArray = array();
$cycleKeys = array();
foreach(array_keys($status->getCycleColors()) as $key)
	$cycleKeys[$key] = 0;

$doStuff = function() use ($status){
	// tcp commands
	$status->getTcpManager()->doStuff();

	// broadcasting
	if($status->getBroadcast() !== null)
		if($status->getConnectedClientStatus() !== 1 && $status->getBroadcast()->isTimeToBroadcast())
			$status->getBroadcast()->broadcast();

	// exit
	if($status->getShouldExit() > 0){
		$status->getTcpManager()->close();
		foreach($status->getDevicePool()->toArray() as $device)
			$device->close();
		$status->saveCacheValues();
		if($status->getShouldExit() == 2){
			echo "\n--------------------\n\n";
			pcntl_exec($_SERVER["_"], $_SERVER["argv"]);
		}
		exit(0);
	}
};

// cycle between animations
echo "Initializing loop\n";
while(true){
	// DISCOVERY PART
	if($discovery->isTimeToCheck()){
		$discovery->checkDisconnected();
		$discovery->checkConnected();
	}

	// NETWORKING PART
	$doStuff();
	if($status->getBroadcast() !== null)
		if(
			$status->getTcpManager()->getLastCommandTime() !== null &&
			time() - $status->getTcpManager()->getLastCommandTime() > 3600
		)
			$status->setConnectedClientStatus(0);

	// ANIMATIONS PART
	if($status->getPlayerStatus() !== null){
		$oldURL = $status->getPlayerStatus()->getArtURL(); // old album art URL for comparison
		if(
			$status->getPlayerStatus()->isPlaying() &&
			!is_null($status->getPlayerStatus()->getAlbumArtColorArray())
		){
			// ok the player is playing and the song has an album art
			if($status->getShowing() === -1 || $status->getShowing() === 0) echo "Using album art color\n";
			$status->setShowing(1);
			if($oldURL !== $status->getPlayerStatus()->getArtURL() || empty($albumArtMediaArray)){
				echo "Album art changed\n";
				$albumArtMediaArray = $status->getPlayerStatus()->getAlbumArtColorArray();
			}
			/** @var Color $color */
			foreach($albumArtMediaArray as $color){
				$oldURL = $status->getPlayerStatus()->getArtURL();
				$fader->timedFadeTo(
					["global" => $color],
					$status->getConfig()->getValue("animationFadeSeconds") ?? 5,
					function() use ($oldURL, $status, $doStuff){
						$doStuff(); // check networking
						// interrupt color cycling if track is not playing or if track skipped
						if(!$status->getPlayerStatus()->isPlaying())
							return true;
						if($oldURL !== $status->getPlayerStatus()->getArtURL())
							return true;

						return false;
					},
					$status->getConfig()->getValue("albumArtColorsOverChosenColors") ?? true
				);
				// interrupt color cycling if track is not playing or if track skipped
				if(!$status->getPlayerStatus()->isPlaying())
					continue 2;

				if($oldURL !== $status->getPlayerStatus()->getArtURL()){
					echo "Album art changed\n";
					$albumArtMediaArray = $status->getPlayerStatus()->getAlbumArtColorArray();
					continue 2;
				}
			}
			continue;
		}else{
			$albumArtMediaArray = array();
		}
	}

	// CHOSEN COLORS
	if($status->getUserChosenColor() !== null){
		if($status->getShowing() === -1 || $status->getShowing() === 1) echo "Using chosen global color\n";
		$status->setShowing(0);
		$fader->timedFadeTo(
			["global" => $status->getUserChosenColor()],
			$status->getConfig()->getValue("normalFadeSeconds") ?? 2,
			function() use ($status, $doStuff){
				$doStuff(); // check networking
				// check if music is playing
				if(
					$status->getPlayerStatus() !== null &&
					$status->getPlayerStatus()->isPlaying()
				){
					return true;
				}
				// check if custom color is unset
				if($status->getUserChosenColor() === null) return true;

				return false;
			}
		);
		continue;
	}

	// COLOR CYCLE
	if(($status->getConfig()->getValue("idleMode") ?? "color-cycle") == "color-cycle"){
		if($status->getShowing() === -1 || $status->getShowing() === 1) echo "Using color cycling\n";
		$status->setShowing(0);

		$colors = array();
		foreach($status->getCycleColors() as $id => $col){
			$count = count($col);
			if($cycleKeys[$id] >= $count) $cycleKeys[$id] = 0;
			$colors[$id] = $col[$cycleKeys[$id]++];
		}

		$fader->timedFadeTo(
			$colors,
			$status->getConfig()->getValue("animationFadeSeconds") ?? 5,
			function() use ($status, $doStuff){
				$doStuff(); // check networking
				// check if music is playing
				if(
					$status->getPlayerStatus() !== null &&
					$status->getPlayerStatus()->isPlaying()
				){
					return true;
				}
				// check if custom color is set
				if($status->getUserChosenColor() !== null) return true;

				return false;
			}
		);
		// interrupt color cycling if track is playing
		if(
			$status->getPlayerStatus() !== null &&
			$status->getPlayerStatus()->isPlaying()
		){
			continue;
		}
		// interrupt color cycling if custom color is set
		if($status->getUserChosenColor() !== null) continue;
		continue;
	}

	// WALLPAPER COLOR
	if(
		($status->getConfig()->getValue("idleMode") ?? "color-cycle") == "wallpaper" &&
		$status->getWallpaperColor() !== null
	){
		if($status->getShowing() === -1 || $status->getShowing() === 1) echo "Using wallpaper color\n";
		$status->setShowing(0);
		if($status->isWallpaperChanged()){
			echo "Wallpaper changed\n";
			$status->setWallpaperChanged(false);
		}
		$fader->timedFadeTo(
			["global" => $status->getWallpaperColor()],
			$status->getConfig()->getValue("normalFadeSeconds") ?? 2,
			function() use ($status, $doStuff){
				$doStuff(); // check networking
				// check if music is playing
				if(
					$status->getPlayerStatus() !== null &&
					$status->getPlayerStatus()->isPlaying()
				){
					return true;
				}
				// check if custom color is set
				if($status->getUserChosenColor() !== null) return true;

				return false;
			}
		);
		continue;
	}

	// THEN, DEFAULT
	if(
		($status->getConfig()->getValue("idleMode") ?? "color-cycle") == "default-color" ||
		$status->getWallpaperColor() === null
	){
		if($status->getShowing() === -1 || $status->getShowing() === 1) echo "Using default color\n";
		$status->setShowing(0);
		$fader->timedFadeTo(
			["global" => Color::fromHex($status->getConfig()->getValue("defaultColor") ?? "FFFFFF")],
			$status->getConfig()->getValue("normalFadeSeconds") ?? 2,
			function() use ($status, $doStuff){
				$doStuff(); // check networking
				// check if music is playing
				if(
					$status->getPlayerStatus() !== null &&
					$status->getPlayerStatus()->isPlaying()
				){
					return true;
				}
				// check if custom color is set
				if($status->getUserChosenColor() !== null) return true;

				return false;
			}
		);
	}
}
