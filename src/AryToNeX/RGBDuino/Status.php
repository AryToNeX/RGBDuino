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

/**
 * Class Status
 *
 * @package AryToNeX\RGBDuino
 */
class Status{

	/** @var array */
	private $currentColor;
	/** @var array|null */
	private $userChosenColor;
	/** @var string */
	private $wallpaperURL;
	/** @var Arduino */
	private $arduino;
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
	 * @param Arduino     $arduino
	 * @param null|string $cfgpath
	 */
	public function __construct(Arduino $arduino, ?string $cfgpath = null){
		$this->shouldExit = 0;
		$this->config = new Config($cfgpath);
		$this->arduino = $arduino;
		$this->currentColor = color\Color::fromHexToRgb($this->config->getValue("defaultColor") ?? "FFFFFF");
		$this->tcpManager = new TCPCommandsManager($this, $this->config->getValue("tcpPort") ?? 6969);

		if(($this->config->getValue("useArtColorWhenPlayingMedia") ?? false) && !empty(exec("which playerctl")))
			$this->playerStatus = new PlayerStatus($this, new PlayerCtl());
	}

	/**
	 * @return Arduino
	 */
	public function getArduino() : Arduino{
		return $this->arduino;
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
	 * @return Config
	 */
	public function getConfig() : Config{
		return $this->config;
	}

	/**
	 * @return PlayerStatus
	 */
	public function getPlayerStatus() : PlayerStatus{
		return $this->playerStatus;
	}

	/**
	 * @return TCPCommandsManager
	 */
	public function getTcpManager() : TCPCommandsManager{
		return $this->tcpManager;
	}

	/**
	 * @return string
	 */
	public function getWallpaperURL() : string{
		return $this->wallpaperURL ?? "";
	}

	/**
	 * @param string $wallpaperURL
	 */
	public function setWallpaperURL(string $wallpaperURL) : void{
		$this->wallpaperURL = $wallpaperURL;
	}

	/**
	 * @return array
	 */
	public function getWallpaperColor() : array{
		return Utils::sanitizeColor(
			Utils::dominantColorFromImage($this->getWallpaperURL()),
			$this->config->getValue("minArtSaturation") ?? null,
			$this->config->getValue("minArtLuminance") ?? null
		);
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