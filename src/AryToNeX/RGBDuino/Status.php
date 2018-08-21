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

class Status{

	private $currentColor;
	private $userChosenColor;
	private $wallpaperURL;
	private $arduino;
	private $config;
	private $playerStatus;
	private $tcpManager;
	private $shouldExit;

	public function __construct(Arduino $arduino, ?string $cfgpath = null){
	    $this->shouldExit = 0;
        $this->config = new Config($cfgpath);
		$this->arduino = $arduino;
		$this->currentColor = color\Color::fromHexToRgb($this->config->getValue("defaultColor") ?? "FFFFFF");
        $this->tcpManager = new TCPCommandsManager($this, $this->config->getValue("tcpPort") ?? 6969);

		if(($this->config->getValue("useArtColorWhenPlayingMedia") ?? false) && !empty(exec("which playerctl")))
			$this->playerStatus = new PlayerStatus($this, new PlayerCtl());
	}

	public function getArduino() : Arduino{
		return $this->arduino;
	}

	public function getCurrentColor() : array{
		return $this->currentColor;
	}

	public function setCurrentColor(array $color) : void{
		$this->currentColor = $color;
	}

	public function getUserChosenColor() : ?array{
	    return $this->userChosenColor;
    }

    public function setUserChosenColor(?array $userChosenColor) : void{
        $this->userChosenColor = $userChosenColor;
    }

	public function getConfig() : Config{
	    return $this->config;
    }

	public function getPlayerStatus() : PlayerStatus{
	    return $this->playerStatus;
    }

    public function getTcpManager() : TCPCommandsManager{
	    return $this->tcpManager;
    }

    public function getWallpaperURL() : string{
	    return $this->wallpaperURL ?? "";
    }

    public function setWallpaperURL(string $wallpaperURL) : void{
	    $this->wallpaperURL = $wallpaperURL;
    }

    public function getWallpaperColor() : array{
        return Utils::sanitizeColor(
            Utils::dominantColorFromImage($this->getWallpaperURL()),
            $this->config->getValue("minArtSaturation") ?? null,
            $this->config->getValue("minArtLuminance") ?? null
        );
    }

    public function getShouldExit() : int{
	    return $this->shouldExit;
    }

    public function setShouldExit(int $shouldExit) : void{
	    $this->shouldExit = $shouldExit;
    }
}