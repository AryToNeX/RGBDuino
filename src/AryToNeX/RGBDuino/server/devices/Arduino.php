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

/**
 * Class Arduino
 * @package AryToNeX\RGBDuino\server\devices
 */
abstract class Arduino extends Device{

	/** @var resource */
	protected $stream;
	/** @var string */
	protected $tty;

	public function setActive(bool $isActive) : void{
		parent::setActive($isActive);
		if(!$isActive) $this->sendColorValues(0, 0, 0); // SHUT THE LEDs DOWN YEA
	}

	/**
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 */
	public function sendColorValues(int $r, int $g, int $b) : void{
		parent::sendColorValues($r, $g, $b);

		$r = ($r < 0 ? 0 : ($r > 255 ? 255 : $r));
		$g = ($g < 0 ? 0 : ($g > 255 ? 255 : $g));
		$b = ($b < 0 ? 0 : ($b > 255 ? 255 : $b));

		$color =
			"r" . str_pad(intval($r), 3, '0', STR_PAD_LEFT) .
			"g" . str_pad(intval($g), 3, '0', STR_PAD_LEFT) .
			"b" . str_pad(intval($b), 3, '0', STR_PAD_LEFT);

		// WRITE
		$this->sendData($color);
	}

	/**
	 *
	 */
	public function saveDisplayedColor() : void{
		$this->sendData("save");
	}

}