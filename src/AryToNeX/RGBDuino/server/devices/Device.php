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

namespace AryToNeX\RGBDuino\server\devices;

use AryToNeX\RGBDuino\server\Color;

/**
 * Class Device
 * @package AryToNeX\RGBDuino\server\devices
 */
abstract class Device{

	/** @var bool */
	protected $isActive;
	/** @var Color */
	protected $currentColor;
	/** @var string */
	protected $identifier;

	public function __construct(string $identifier){
		$this->identifier = $identifier;
		$this->isActive = true;
		$this->currentColor = new Color(0, 0, 0); // Placeholder af
	}

	abstract protected function computeIdentifier() : void;

	public function getIdentifier() : string{
		return $this->identifier;
	}

	/**
	 * @return bool
	 */
	abstract public function isConnected() : bool;

	/**
	 * @return bool
	 */
	public function isActive() : bool{
		return $this->isActive;
	}

	/**
	 * @param bool $isActive
	 */
	public function setActive(bool $isActive) : void{
		$this->isActive = $isActive;
	}

	/**
	 * @return Color
	 */
	public function getCurrentColor() : Color{
		return $this->currentColor;
	}

	public function sendColor(Color $color) : void{
		$this->sendColorValues($color->getR(), $color->getG(), $color->getB());
	}

	/**
	 * @param array $rgb
	 */
	public function sendColorArray(array $rgb) : void{
		$this->sendColorValues($rgb["r"], $rgb["g"], $rgb["b"]);
	}

	/**
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 */
	public function sendColorValues(int $r, int $g, int $b) : void{
		$this->currentColor = Color::fromArray(["r" => $r, "g" => $g, "b" => $b]);
		// MUST EXTEND THIS AND CALL THE PARENT
	}

	abstract public function saveDisplayedColor() : void;

	/**
	 * @param string $data
	 */
	abstract protected function sendData(string $data) : void;

	abstract public function close() : void;

}