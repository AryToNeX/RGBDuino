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

namespace AryToNeX\RGBDuino;

use AryToNeX\RGBDuino\arduino\USBArduino;
use AryToNeX\RGBDuino\arduino\BTArduino;

cli_set_process_title("rgbduino-daemon");

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
				$status->getArduinoPool()->add("USB-" . $serial, new USBArduino($serial));
		}

		if($status->getConfig()->getValue("useUsb") ?? false)
			foreach($status->getConfig()->getValue("bluetooth") as $btd)
				$status->getArduinoPool()->add("BT-" . $btd["identifier"], new BTArduino($btd["mac"]));

		//$status->getArduinoPool()->add("FakeArduino", new arduino\FakeArduino()); //
		// DEBUGGING FTW
	}catch(\Exception $e){
		echo "Exception: " . $e->getMessage() . " - Waiting 2 seconds...\n";
		$status = null;
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

if($status->getConfig()->getValue("animateArtColor") ?? true) $albumArtMediaArray = array();

$doStuff = function() use ($status){
	$status->getTcpManager()->doStuff();
	if($status->getShouldExit() > 0){
		$status->getTcpManager()->close();
		foreach($status->getArduinoPool()->toArray() as $arduino)
			$arduino->close();
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
	try{
		if($status->getPlayerStatus() !== null){
			$oldURL = $status->getPlayerStatus()->getArtURL(); // old album art URL for comparison
			if(
				$status->getPlayerStatus()->checkForPlayers() &&
				$status->getPlayerStatus()->isPlaying() &&
				$status->getPlayerStatus()->updateArtURL() // this one updates artURL from player status
			){
				// ok the player is playing and the song has an album art
				if($status->getConfig()->getValue("animateArtColor") ?? true){
					if($status->getShowing() === -1 || $status->getShowing() === 0) echo "Using animated art color\n";
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
								if(
									!$status->getPlayerStatus()->checkForPlayers() ||
									!(
										$status->getPlayerStatus()->isPlaying() &&
										$status->getPlayerStatus()->updateArtURL()
									)
								){
									return true;
								}
								if($oldURL !== $status->getPlayerStatus()->getArtURL()){
									return true;
								}

								return false;
							}
						);
						// interrupt color cycling if track is not playing or if track skipped
						if(
							!$status->getPlayerStatus()->checkForPlayers() ||
							!(
								$status->getPlayerStatus()->isPlaying() &&
								$status->getPlayerStatus()->updateArtURL()
							)
						){
							continue 2;
						}
						if($oldURL !== $status->getPlayerStatus()->getArtURL()){
							echo "Album art changed\n";
							$albumArtMediaArray = $status->getPlayerStatus()->getAlbumArtColorArray();
							continue 2;
						}
					}
				}else{
					if($status->getShowing() === 0 || $oldURL !== $status->getPlayerStatus()->getArtURL()){
						if($status->getShowing() === -1 || $status->getShowing() === 0) echo "Using art color\n";
						$status->setShowing(1);
						if($oldURL !== $status->getPlayerStatus()->getArtURL())
							echo "Album art changed\n";

						$fader->timedFadeTo(
							$status->getPlayerStatus()->getAlbumArtColor(),
							$status->getConfig()->getValue("fadeSeconds") ?? 2
						);
					}
				}
				continue;
			}else{
				if($status->getConfig()->getValue("animateArtColor") ?? true) $albumArtMediaArray = array();
			}
		}
	}catch(\Exception $e){
		$status->getPlayerStatus()->getPlayerCtl()->setActivePlayer(
			$status->getPlayerStatus()->getPlayerCtl()->getPlayers()[0] ?? null
		);
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
					$status->getPlayerStatus()->checkForPlayers() &&
					$status->getPlayerStatus()->isPlaying() &&
					$status->getPlayerStatus()->updateArtURL()
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
						$status->getPlayerStatus()->checkForPlayers() &&
						$status->getPlayerStatus()->isPlaying() &&
						$status->getPlayerStatus()->updateArtURL()
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
				$status->getPlayerStatus()->checkForPlayers() &&
				$status->getPlayerStatus()->isPlaying() &&
				$status->getPlayerStatus()->updateArtURL()
			){
				continue 2;
			}
			// interrupt color cycling if custom color is set
			if($status->getUserChosenColor() !== null) continue 2;
		}
		continue;
	}

	// WALLPAPER COLOR
	if(($status->getConfig()->getValue("idleMode") ?? "color-cycle") == "wallpaper"){
		if($status->getShowing() === -1 || $status->getShowing() === 1) echo "Using wallpaper color\n";
		$status->setShowing(0);
		if(Utils::getWallpaperURL() !== $status->getWallpaperURL()){
			echo "Wallpaper changed; calculating new color\n";
			$status->setWallpaperURL(Utils::getWallpaperURL());
			$status->setWallpaperColor($status->calculateWallpaperColor());
			echo "Wallpaper color calculated\n";
		}
		$fader->timedFadeTo(
			$status->getWallpaperColor(),
			$status->getConfig()->getValue("fadeSeconds") ?? 2,
			function() use ($status, $doStuff){
				$doStuff(); // check networking
				// check if music is playing
				if(
					$status->getPlayerStatus() !== null &&
					$status->getPlayerStatus()->checkForPlayers() &&
					$status->getPlayerStatus()->isPlaying() &&
					$status->getPlayerStatus()->updateArtURL()
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
	if(($status->getConfig()->getValue("idleMode") ?? "color-cycle") == "defaultColor"){
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
					$status->getPlayerStatus()->checkForPlayers() &&
					$status->getPlayerStatus()->isPlaying() &&
					$status->getPlayerStatus()->updateArtURL()
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
