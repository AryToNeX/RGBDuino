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

	/** @var array */
	private $currentColor;
	/** @var array|null */
	private $userChosenColor;
	/** @var int */
	private $showing; // -1 = just started, not showing anything, 0 = normal animations, 1 = music art
	/** @var array */
	private $wallpaperColor;
	/** @var bool */
	private $isWallpaperChanged;
	/** @var ArduinoPool */
	private $arduinoPool;
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
		$this->shouldExit = 0;
		$this->config = new Config($cfgpath);
		$this->arduinoPool = new ArduinoPool();
		$this->currentColor = color\Color::fromHexToRgb($this->config->getValue("defaultColor") ?? "FFFFFF");
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

		$this->wallpaperColor = $values["wallColor"];
		$this->userChosenColor = $values["chosenColor"];
		$this->isWallpaperChanged = true;
	}

	public function saveCacheValues() : void{
		@mkdir("/home/" . exec("whoami") . "/.cache/RGBDuino", 0755, true);
		$values = array(
			"wallColor"   => $this->wallpaperColor,
			"chosenColor" => $this->userChosenColor,
		);
		file_put_contents("/home/" . exec("whoami") . "/.cache/RGBDuino/status.json", json_encode($values));
	}

	/**
	 * @return ArduinoPool
	 */
	public function getArduinoPool() : ArduinoPool{
		return $this->arduinoPool;
	}

	/**
	 * @return array
	 */
	public function getCurrentColor() : array{
		return $this->currentColor;
	}

	/**
	 * @param array $color
	 */
	public function setCurrentColor(array $color) : void{
		$this->currentColor = $color;
	}

	/**
	 * @return array|null
	 */
	public function getUserChosenColor() : ?array{
		return $this->userChosenColor;
	}

	/**
	 * @param array|null $userChosenColor
	 */
	public function setUserChosenColor(?array $userChosenColor) : void{
		$this->userChosenColor = $userChosenColor;
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
	 * @return array
	 */
	public function getWallpaperColor() : ?array{
		return $this->wallpaperColor;
	}

	/**
	 * @param array $wallpaperColor
	 */
	public function setWallpaperColor(?array $wallpaperColor) : void{
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