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

/**
 * Class Status
 *
 * @package AryToNeX\RGBDuino\server
 */
class Status{

	/** @var Color[]|null */
	private $userChosenColorArray;
	/** @var int */
	private $showing; // -1 = just started, not showing anything, 0 = normal animations, 1 = music art
	/** @var Color */
	private $wallpaperColor;
	/** @var bool */
	private $isWallpaperChanged;
	/** @var DevicePool */
	private $devicePool;
	/** @var Config */
	private $config;
	/** @var PlayerStatus */
	private $playerStatus;
	/** @var TCPCommandsManager */
	private $tcpManager;
	/** @var int */
	private $shouldExit;

	/**
	 * Status constructor.
	 *
	 * @param null|string $cfgpath
	 */
	public function __construct(?string $cfgpath = null){
		$this->userChosenColorArray = array();
		$this->shouldExit = 0;
		$this->config = new Config($cfgpath);
		$this->devicePool = new DevicePool();
		$this->tcpManager = new TCPCommandsManager($this, $this->config->getValue("tcpPort") ?? 6969);

		if($this->config->getValue("acceptAlbumArtColors") ?? false)
			$this->playerStatus = new PlayerStatus($this);

		// restore previous status for certain values
		$this->restoreValues();
	}

	protected function restoreValues() : void{
		if(!is_file("/home/" . exec("whoami") . "/.cache/RGBDuino/status.json")) return;

		$values = json_decode(
			file_get_contents(
				"/home/" . exec("whoami") . "/.cache/RGBDuino/status.json"
			),
			true
		);

		if(!isset($values) || empty($values)) return;

		$this->wallpaperColor = $values["wallColor"];
		$colorArr = array();
		foreach($values["chosenColorArr"] as $id => $hex)
			if(isset($hex))
				$colorArr[$id] = Color::fromHex($hex);
		$this->userChosenColorArray = $colorArr;
		$this->isWallpaperChanged = true;
	}

	public function saveCacheValues() : void{
		@mkdir("/home/" . exec("whoami") . "/.cache/RGBDuino", 0755, true);
		$colorArr = array();
		foreach($this->userChosenColorArray as $id => $color)
			if(isset($color))
				$colorArr[$id] = $color->asHex();
		$values = array(
			"wallColor"      => $this->wallpaperColor,
			"chosenColorArr" => $colorArr,
		);
		file_put_contents("/home/" . exec("whoami") . "/.cache/RGBDuino/status.json", json_encode($values));
	}

	/**
	 * @return DevicePool
	 */
	public function getDevicePool() : DevicePool{
		return $this->devicePool;
	}

	/**
	 * @param null|string $identifier
	 *
	 * @return Color|null
	 */
	public function getUserChosenColor(?string $identifier = null) : ?Color{
		if(isset($identifier)) return $this->userChosenColorArray[$identifier] ?? null;

		return $this->userChosenColorArray["global"] ?? null;
	}

	/**
	 * @return Color[]
	 */
	public function getUserChosenColorArray() : array{
		return $this->userChosenColorArray;
	}

	/**
	 * @param Color|null  $userChosenColor
	 * @param null|string $identifier
	 */
	public function setUserChosenColor(?Color $userChosenColor, ?string $identifier = null) : void{
		if(isset($identifier)) $this->userChosenColorArray[$identifier] = $userChosenColor;
		else $this->userChosenColorArray["global"] = $userChosenColor;
	}

	/**
	 * @param Color[] $userChosenColorArray
	 */
	public function setUserChosenColorArray(array $userChosenColorArray) : void{
		$this->userChosenColorArray = $userChosenColorArray;
	}

	/**
	 * @return int
	 */
	public function getShowing() : int{
		return $this->showing ?? -1;
	}

	/**
	 * @param int $showing
	 */
	public function setShowing(int $showing) : void{
		$this->showing = $showing;
	}

	/**
	 * @return Config
	 */
	public function getConfig() : Config{
		return $this->config;
	}

	/**
	 * @return PlayerStatus
	 */
	public function getPlayerStatus() : ?PlayerStatus{
		return $this->playerStatus;
	}

	/**
	 * @return TCPCommandsManager
	 */
	public function getTcpManager() : TCPCommandsManager{
		return $this->tcpManager;
	}

	/**
	 * @return Color
	 */
	public function getWallpaperColor() : ?Color{
		return $this->wallpaperColor;
	}

	/**
	 * @param Color $wallpaperColor
	 */
	public function setWallpaperColor(?Color $wallpaperColor) : void{
		$this->wallpaperColor = $wallpaperColor;
	}

	public function isWallpaperChanged() : bool{
		return $this->isWallpaperChanged;
	}

	/**
	 * @param bool $isWallpaperChanged
	 */
	public function setWallpaperChanged(bool $isWallpaperChanged) : void{
		$this->isWallpaperChanged = $isWallpaperChanged;
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
	public function setShouldExit(int $shouldExit) : void{
		$this->shouldExit = $shouldExit;
	}
}