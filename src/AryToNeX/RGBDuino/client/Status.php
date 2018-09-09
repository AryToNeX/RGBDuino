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

use AryToNeX\RGBDuino\client\exceptions\MalformedIPException;
use AryToNeX\RGBDuino\client\exceptions\UnresponsiveServerException;

/**
 * Class Status
 * @package AryToNeX\RGBDuino\client
 */
class Status{

	/** @var Config */
	protected $config;
	/** @var BroadcastReceiver */
	protected $broadcastReceiver;
	/** @var Communicator */
	protected $communicator;
	/** @var PlayerDetails */
	protected $playerDetails;
	/** @var MiniPlayerCtl */
	protected $ctl;
	/** @var string */
	protected $deskEnv;
	/** @var string */
	protected $wallURL;
	/** @var int */
	protected $shouldExit;

	/**
	 * Status constructor.
	 *
	 * @param null|string $cfgpath
	 */
	public function __construct(?string $cfgpath = null){
		$this->shouldExit = 0;
		$this->config = new Config($cfgpath);

		if($this->config->getValue("useLocalDiscovery") ?? true)
			$this->broadcastReceiver = new BroadcastReceiver($this, $this->config->getValue("discoveryPort") ?? 6969);

		try{
			$this->communicator = new Communicator(
				$this->config->getValue("serverIp") ?? "0.0.0.0",
				$this->config->getValue("serverPort") ?? 6969
			);
		}catch(MalformedIPException | UnresponsiveServerException $e){
			$this->communicator = null;
		}

		if($this->config->getValue("sendPlayerStatus") ?? true && !empty(exec("which playerctl"))){
			echo "Player details are active!\n";
			$this->playerDetails = new PlayerDetails();
			$this->ctl = new MiniPlayerCtl();
		}else echo "Player details aren't active...\n";

		if($this->config->getValue("sendWallpaperColor") ?? true){
			echo "Wallpaper color sending is active!\n";
			$this->deskEnv = Utils::getDesktopEnvironment();
		}else echo "Wallpaper color sending isn't active...\n";
	}

	/**
	 * @return Config
	 */
	public function getConfig() : Config{
		return $this->config;
	}

	/**
	 * @return BroadcastReceiver|null
	 */
	public function getBroadcastReceiver() : ?BroadcastReceiver{
		return $this->broadcastReceiver;
	}

	/**
	 * @return Communicator|null
	 */
	public function getCommunicator() : ?Communicator{
		return $this->communicator;
	}

	/**
	 * @param Communicator $communicator
	 */
	public function setCommunicator(?Communicator $communicator) : void{
		$this->communicator = $communicator;
	}

	/**
	 * @return null|PlayerDetails
	 */
	public function getPlayerDetails() : ?PlayerDetails{
		return $this->playerDetails;
	}

	/**
	 * @return null|MiniPlayerCtl
	 */
	public function getCtl() : ?MiniPlayerCtl{
		return $this->ctl;
	}

	/**
	 * @return null|string
	 */
	public function getDeskEnv() : ?string{
		return $this->deskEnv;
	}

	/**
	 * @return null|string
	 */
	public function getCurrentWallpaperURL() : ?string{
		return $this->wallURL;
	}

	/**
	 * @param null|string $wallURL
	 */
	public function setCurrentWallpaperURL(?string $wallURL) : void{
		$this->wallURL = $wallURL;
	}

	/**
	 * @param null|int $colors
	 *
	 * @return null|Color[]
	 */
	public function getAlbumArtColorArray(int $colors = 5) : ?array{
		if($colors < 1) $colors = 1;
		if($this->playerDetails->getAlbumArtURL() === null) return null;
		$rgbArr = Utils::dominantColorArrayFromImage($this->playerDetails->getAlbumArtURL(), $colors);
		foreach($rgbArr as $i => $rgb){
			$color = Color::fromArray($rgb);
			$color->sanitize(
				$this->getConfig()->getValue("minArtSaturation") ?? null,
				$this->getConfig()->getValue("minArtLuminance") ?? null
			);
			$rgbArr[$i] = $color;
		}

		return $rgbArr;
	}

	/**
	 * @return int
	 */
	public function getShouldExit() : int{
		return $this->shouldExit;
	}

	/**
	 * @param int $shouldExit
	 */
	public function setShouldExit(int $shouldExit = 1) : void{
		$this->shouldExit = $shouldExit;
	}

	/**
	 * @return bool
	 */
	public function pingServer() : bool{
		$tries = 0;
		do{
			if($this->communicator->tryPing())
				return true;
			else
				$tries++;
		}while($tries < 5);

		return false;
	}

}