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

use AryToNeX\RGBDuino\exceptions\NoAlbumArtException;

/**
 * Class PlayerStatus
 *
 * @package AryToNeX\RGBDuino
 */
class PlayerStatus{

	/** @var Status */
	private $owner;
	/** @var PlayerCtl */
	private $playerctl;
	/** @var string */
	private $artURL;

	/**
	 * PlayerStatus constructor.
	 *
	 * @param Status    $owner
	 * @param PlayerCtl $playerctl
	 */
	public function __construct(Status $owner, PlayerCtl $playerctl){
		$this->owner = $owner;
		$this->playerctl = $playerctl;
	}

	/**
	 * @return PlayerCtl
	 */
	public function getPlayerCtl(){
		return $this->playerctl;
	}

	/**
	 * @return bool
	 */
	public function updateArtURL() : bool{
		try{
			$this->artURL = str_replace( // spotify sucks so we get the art without the spotify logo in the bottom right corner
				"https://open.spotify.com/",
				"http://i.scdn.co/",
				$this->playerctl->getAlbumArtURL()
			);
		}catch(\Exception | NoAlbumArtException $e){
			$this->artURL = "";

			return false;
		}

		return true;
	}

	/**
	 * @return string
	 */
	public function getArtURL() : string{
		return $this->artURL ?? "";
	}

	/**
	 * @return bool
	 */
	public function isPlaying() : bool{
		try{
			return $this->playerctl->getStatus() == "Playing";
		}catch(\Exception $e){
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function checkForPlayers() : bool{
		if(!empty($this->playerctl->getPlayers())){
			if(!in_array($this->playerctl->getActivePlayer(), $this->playerctl->getPlayers()))
				$this->playerctl->setActivePlayer($this->playerctl->getPlayers()[0] ?? null);

			return true;
		}

		return false;
	}

	/** @throws \Exception */
	public function getAlbumArtColor() : array{
		return Utils::sanitizeColor(
			Utils::dominantColorFromImage($this->artURL),
			$this->owner->getConfig()->getValue("minArtSaturation") ?? null,
			$this->owner->getConfig()->getValue("minArtLuminance") ?? null
		);
	}

	/**
	 * @param int $colors
	 *
	 * @return array|null
	 */
	public function getAlbumArtColorArray(int $colors = 5) : ?array{
		$rgbArr = Utils::dominantColorArrayFromImage($this->artURL, $colors);
		foreach($rgbArr as $i => $rgb) $rgbArr[$i] = Utils::sanitizeColor(
			$rgb,
			$this->owner->getConfig()->getValue("minArtSaturation") ?? null,
			$this->owner->getConfig()->getValue("minArtLuminance") ?? null
		);

		return $rgbArr;
	}

}