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
 * Class FaderHelper
 *
 * @package AryToNeX\RGBDuino
 */
class FaderHelper{

	/** @var Status */
	private $status;

	/**
	 * FaderHelper constructor.
	 *
	 * @param Status $status
	 */
	public function __construct(Status $status){
		$this->status = $status;
	}

	/**
	 * @param array         $rgb
	 * @param int           $fadeMultiplier
	 * @param callable|null $shouldStop
	 */
	public function fadeTo(array $rgb, int $fadeMultiplier = 1, ?callable $shouldStop = null) : void{

		if($this->status->getCurrentColor() === $rgb){
			return;
		}

		for($i = 0; $i <= 100; $i += $fadeMultiplier){
			usleep(20000);
			$mixedColor = color\Color::mixColors($this->status->getCurrentColor(), $rgb, $i);
			$this->status->getArduino()->sendColor($mixedColor["r"], $mixedColor["g"], $mixedColor["b"]);

			if(isset($shouldStop) and $shouldStop()){
				$this->status->setCurrentColor($mixedColor);

				return;
			}
		}
		$this->status->getArduino()->sendColor($rgb["r"], $rgb["g"], $rgb["b"]);
		$this->status->setCurrentColor($rgb);
	}

	/**
	 * @param array         $rgb
	 * @param float         $seconds
	 * @param callable|null $shouldStop
	 */
	public function timedFadeTo(array $rgb, float $seconds = 2, ?callable $shouldStop = null) : void{

		if($this->status->getCurrentColor() === $rgb){
			return;
		}
		$multiplier = 1;
		$microseconds = ($seconds * 1000000) / 100;
		if($microseconds < 20000){
			$microseconds = 20000; // 2 millis
			$multiplier = intval(2 / $seconds); // 2 millis multiplier
		}
		for($i = 0; $i <= 100; $i += $multiplier){
			usleep($microseconds);
			$mixedColor = color\Color::mixColors($this->status->getCurrentColor(), $rgb, $i);
			$this->status->getArduino()->sendColor($mixedColor["r"], $mixedColor["g"], $mixedColor["b"]);

			if(isset($shouldStop) and $shouldStop()){
				$this->status->setCurrentColor($mixedColor);

				return;
			}
		}
		$this->status->getArduino()->sendColor($rgb["r"], $rgb["g"], $rgb["b"]);
		$this->status->setCurrentColor($rgb);
	}

}