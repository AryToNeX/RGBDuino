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
 * Class FakeArduinoThatJustOutputsEverything
 *
 * @package AryToNeX\RGBDuino
 */
class FakeArduinoThatJustOutputsEverything extends Arduino{

	/**
	 * FakeArduinoThatJustOutputsEverything constructor.
	 *
	 * @param null|string $tty
	 */
	public function __construct(?string $tty = null){
		echo "Fake arduino opened!\n";
	}

	/**
	 * @param array $rgb
	 */
	public function sendColorArray(array $rgb) : void{
		$this->sendColor($rgb["r"], $rgb["g"], $rgb["b"]);
	}

	/**
	 * @param $r
	 * @param $g
	 * @param $b
	 */
	public function sendColor($r, $g, $b) : void{
		$r = ($r < 0 ? 0 : ($r > 255 ? 255 : $r));
		$g = ($g < 0 ? 0 : ($g > 255 ? 255 : $g));
		$b = ($b < 0 ? 0 : ($b > 255 ? 255 : $b));

		$color =
			"r" . str_pad(intval($r), 3, '0', STR_PAD_LEFT) .
			"g" . str_pad(intval($g), 3, '0', STR_PAD_LEFT) .
			"b" . str_pad(intval($b), 3, '0', STR_PAD_LEFT);

		// WRITE
		//echo "\e[1A\e[2K\r";
		echo "Fake Arduino received: $color\n";
	}

	public function saveDisplayedColor() : void{
		echo "Fake Arduino received: save\n";
	}

	public function close() : void{
		echo "Fake Arduino connection closed!\n";
	}

}