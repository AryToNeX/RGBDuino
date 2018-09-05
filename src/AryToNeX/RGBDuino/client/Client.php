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

namespace AryToNeX\RGBDuino\client;

cli_set_process_title("rgbduino-client");

echo "RGBDuino Client initializing!\n";

$status = new Status();
pcntl_async_signals(true);
pcntl_signal(SIGINT, function() use ($status){ $status->setShouldExit(1); });
pcntl_signal(SIGUSR1, function() use ($status){ $status->setShouldExit(2); });
pcntl_signal(
	SIGUSR2,
	function() use ($status){
		$updater = new Updater();
		if($updater->update()){
			echo "Client updated. Restarting...\n";
			$status->setShouldExit(2);
		}else{
			echo "Client received update signal but no updates found\n";
		}
	}
);


echo "Pinging server...\n";
if(!$status->pingServer()){
	echo "Server is off! Exiting...\n";
	exit(-1);
}
echo "Server is on!\n";

// set empty player details
$status->getPlayerDetails()->setPlaying(false);
$status->getPlayerDetails()->setAlbumArtURL(null);
$status->getPlayerDetails()->setArtColors(null);
$status->getCommunicator()->sendPlayerDetails($status->getPlayerDetails());

while(true){
	// check if we should exit
	if($status->getShouldExit() > 0){
		if($status->getShouldExit() == 2){
			echo "\n--------------------\n\n";
			pcntl_exec($_SERVER["_"], $_SERVER["argv"]);
		}else
			exit(0);
	}

	// wallpaper
	if($status->getDeskEnv() !== null){
		$url = Utils::getWallpaperURL($status->getDeskEnv());
		if($url !== $status->getCurrentWallpaperURL()){
			echo "Wallpaper changed; computing color...\n";
			$color = Utils::dominantColorFromImage($url);
			$color = Utils::sanitizeColor(
				$color,
				$status->getConfig()->getValue("minArtSaturation") ?? null,
				$status->getConfig()->getValue("minArtLuminance") ?? null
			);
			if(!$status->getCommunicator()->sendWallpaperColor($color) && !$status->pingServer()){
				echo "Server went off! Exiting...\n";
				$status->setShouldExit(1);
				continue;
			}
			$status->setCurrentWallpaperURL($url);
			echo "Color computed and sent!\n";
		}
		unset($old);
	}

	// album art
	if($status->getCtl() !== null){
		$old = clone $status->getPlayerDetails();

		if($status->getCtl()->getActivePlayer() === null){
			$pl = $status->getCtl()->getPlayers();
			if(!empty($pl)){
				echo "Setting music player to $pl[0]\n";
				$status->getCtl()->setActivePlayer($pl[0]);
			}else continue;
		}

		try{
			$isPlaying = $status->getCtl()->isPlaying();
			$artUrl = $status->getCtl()->getAlbumArtURL();
		}catch(\Exception $e){
			$isPlaying = false;
		}

		if(!$isPlaying){
			$artUrl = null;
			$artColors = null;
		}

		$status->getPlayerDetails()->setPlaying($isPlaying ?? false);
		$status->getPlayerDetails()->setAlbumArtURL($artUrl ?? null);
		$artColors = $status->getAlbumArtColorArray($status->getConfig()->getValue("colorsToExtract") ?? 5);
		$status->getPlayerDetails()->setArtColors($artColors ?? null);

		if($status->getPlayerDetails()->computeDiff($old)){
			echo "Sending music info..\n";
			if(
				!$status->getCommunicator()->sendPlayerDetails($status->getPlayerDetails()) &&
				!$status->pingServer()
			){
				echo "Server went off! Exiting...\n";
				$status->setShouldExit(1);
				continue;
			}
		}
		unset($old);
	}
}