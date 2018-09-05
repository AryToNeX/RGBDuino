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
 * Class PlayerStatus
 *
 * @package AryToNeX\RGBDuino\server
 */
class PlayerStatus{

	/** @var Status */
	private $owner;
	/** @var string */
	private $artURL;
	/** @var bool */
	private $isPlaying;
	/** @var array */
	private $artColors;

	/**
	 * PlayerStatus constructor.
	 *
	 * @param Status    $owner
	 */
	public function __construct(Status $owner){
		$this->owner = $owner;
	}

	public function setArtURL($artUrl) : void{
		$this->artURL = $artUrl;
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
		return $this->isPlaying ?? false;
	}

	public function setPlaying($isPlaying) : void{
		$this->isPlaying = $isPlaying;
	}

	/**
	 * @return array|null
	 */
	public function getAlbumArtColorArray() : ?array{
		return $this->artColors;
	}

	public function setAlbumArtColorArray(?array $colors) : void{
		$this->artColors = $colors;
	}

}