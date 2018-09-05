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

use AryToNeX\RGBDuino\server\arduino\USBArduino;
use AryToNeX\RGBDuino\server\arduino\BTArduino;

cli_set_process_title("rgbduino-server");

$status = new Status();

echo "Initializing Arduino connections...\n";
$tries = 0;
do{
	$good = true;
	try{
		if($status->getConfig()->getValue("useUsb") ?? true){
			$serials = Utils::detectUSBArduino();
			if(empty($serials)) throw new \Exception("No Arduino USB devices found");
			foreach($serials as $serial)
				$status->getArduinoPool()->add(
					"USB-" . $serial,
					new USBArduino($serial, $status->getConfig()->getValue("baudRate") ?? 9600)
				);
		}

		if($status->getConfig()->getValue("useUsb") ?? false){
			foreach($status->getConfig()->getValue("bluetooth") as $btd)
				$status->getArduinoPool()->add(
					"BT-" . $btd["identifier"],
					new BTArduino(
						$btd["mac"],
						$btd["rfcommPort"] ?? null,
						$status->getConfig()->getValue("baudRate") ?? 9600
					)
				);
		}

		//$status->getArduinoPool()->add("FakeArduino", new arduino\FakeArduino());
		// DEBUGGING FTW
	}catch(\Exception $e){
		echo "Exception: " . $e->getMessage() . " - Waiting 2 seconds...\n";
		$good = false;
		$tries++;
		sleep(2);
	}
}while(!$good && $tries < 5);

if(!$good){
	echo "Can't connect. Exiting...\n";
	exec(
		"zenity --error --ellipsize \
    --title=\"RGBDuino Error\" \
    --text=\"RGBDuino can't connect to the LED strip because of an error and thus it stopped.\nPlease restore your connections, then use the CLI utility 'rgbduino start' to start it again.\" \
    --ok-label=\"That's so sad, Alexa play Despacito\""
	);
	die;
}

unset($good);
unset($tries);

// Started successfully!
echo "Arduino connections established correctly!\n";
exec(
	"notify-send -u normal -i arduino \
\"RGBDuino is started and working!\" \
\"View the log via <b>screen -r rgbduino</b>\""
);

$fader = new FaderHelper($status);

pcntl_async_signals(true);
pcntl_signal(
	SIGINT,
	function() use ($status){
		$status->setShouldExit(1);
	}
);

// start with default color
foreach($status->getArduinoPool()->toArray() as $arduino)
	$arduino->sendColorArray(
		color\Color::fromHexToRgb($status->getConfig()->getValue("defaultColor") ?? "FFFFFF")
	);

// save to EEPROM if config says yes
if($status->getConfig()->getValue("saveDefaultColorToEEPROM") ?? false){
	usleep(30000); // we need to keep calm and let the Arduino display the color first
	foreach($status->getArduinoPool()->toArray() as $arduino)
		$arduino->saveDisplayedColor();
	usleep(30000); // then we need to relax a little more to let the Arduino save the color
}

$albumArtMediaArray = array();

$doStuff = function() use ($status){
	$status->getTcpManager()->doStuff();
	if($status->getShouldExit() > 0){
		$status->getTcpManager()->close();
		foreach($status->getArduinoPool()->toArray() as $arduino)
			$arduino->close();
		$status->saveCacheValues();
		if($status->getShouldExit() == 2){
			echo "\n--------------------\n\n";
			pcntl_exec($_SERVER["_"], $_SERVER["argv"]);
		}else
			exit(0);
	}
};

// cycle between animations
echo "Initializing loop\n";
while(true){
	// NETWORKING PART
	$doStuff();

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
			foreach($albumArtMediaArray as $rgb){
				$oldURL = $status->getPlayerStatus()->getArtURL();
				$fader->timedFadeTo(
					$rgb,
					$status->getConfig()->getValue("artFadeSeconds") ?? 2,
					function() use ($oldURL, $status, $doStuff){
						$doStuff(); // check networking
						// interrupt color cycling if track is not playing or if track skipped
						if(!$status->getPlayerStatus()->isPlaying())
							return true;
						if($oldURL !== $status->getPlayerStatus()->getArtURL())
							return true;

						return false;
					}
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
		if($status->getShowing() === -1 || $status->getShowing() === 1) echo "Using chosen color\n";
		$status->setShowing(0);
		$fader->timedFadeTo(
			$status->getUserChosenColor(),
			$status->getConfig()->getValue("fadeSeconds") ?? 2,
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
		foreach(($status->getConfig()->getValue("cycleColors") ?? ["FFFFFF", "000000"]) as $hex){
			$fader->timedFadeTo(
				color\Color::fromHexToRgb($hex),
				$status->getConfig()->getValue("cycleFadeSeconds") ?? 2,
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
				continue 2;
			}
			// interrupt color cycling if custom color is set
			if($status->getUserChosenColor() !== null) continue 2;
		}
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
			$status->getWallpaperColor(),
			$status->getConfig()->getValue("fadeSeconds") ?? 2,
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
			color\Color::fromHexToRgb($status->getConfig()->getValue("defaultColor") ?? "FFFFFF"),
			$status->getConfig()->getValue("fadeSeconds") ?? 2,
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
