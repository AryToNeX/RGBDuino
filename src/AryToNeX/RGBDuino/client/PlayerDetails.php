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

/**
 * Class PlayerDetails
 * @package AryToNeX\RGBDuino\client
 */
class PlayerDetails{

	/** @var bool */
	protected $isPlaying;
	/** @var string */
	protected $artUrl;
	/** @var array */
	protected $artColors;

	/**
	 * @return bool
	 */
	public function isPlaying() : bool{
		return $this->isPlaying ?? false;
	}

	/**
	 * @param bool $isPlaying
	 */
	public function setPlaying(bool $isPlaying) : void{
		$this->isPlaying = $isPlaying;
	}

	/**
	 * @return null|string
	 */
	public function getAlbumArtURL() : ?string{
		return $this->artUrl;
	}

	/**
	 * @param null|string $artUrl
	 */
	public function setAlbumArtURL(?string $artUrl) : void{
		$this->artUrl = $artUrl;
	}

	/**
	 * @return null|array
	 */
	public function getArtColors() : ?array{
		return $this->artColors;
	}

	/**
	 * @param null|array $artColors
	 */
	public function setArtColors(?array $artColors) : void{
		$this->artColors = $artColors;
	}

	/**
	 * @param PlayerDetails $playerDetails
	 *
	 * @return bool
	 */
	public function computeDiff(PlayerDetails $playerDetails){
		if($this->isPlaying() !== $playerDetails->isPlaying()) return true;
		if($this->getAlbumArtURL() !== $playerDetails->getAlbumArtURL()) return true;

		return false;
	}

	/**
	 * @return string
	 */
	public function toHash(){
		$preHash = array(
			"playing" => $this->isPlaying,
			"url"     => $this->artUrl,
			"colors"  => $this->artColors,
		);

		return base64_encode(json_encode($preHash));
	}

}