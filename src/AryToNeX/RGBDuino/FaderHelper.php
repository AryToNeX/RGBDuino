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

use AryToNeX\RGBDuino\color\Color;

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

		$pool = $this->status->getArduinoPool()->toArray();
		$shades = $this->getShades($this - $this->status->getCurrentColor(), $rgb);

		for($i = 0; $i <= 100; $i += $fadeMultiplier){
			usleep(20000);
			foreach($pool as $arduino)
				$arduino->sendColorArray($shades[$i]);

			if(isset($shouldStop) and $shouldStop()){
				$this->status->setCurrentColor($shades[$i]);

				return;
			}
		}

		foreach($pool as $arduino)
			$arduino->sendColorArray($rgb);
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

		$pool = $this->status->getArduinoPool()->toArray();
		$shades = $this->adjustSteppedShades(
			$this->mapShadesToSeconds(
				$this->getShades($this->status->getCurrentColor(), $rgb),
				$seconds
			)
		);

		$beginSec = microtime(true);
		foreach($shades as $shade){
			$nowSec = microtime(true) - $beginSec;

			while($shade["time"] > $nowSec){
				$nowSec = microtime(true) - $beginSec;
				usleep(1);
			}

			foreach($pool as $arduino)
				$arduino->sendColorArray($shade["shade"]);

			if(isset($shouldStop) and $shouldStop()){
				$this->status->setCurrentColor($shade["shade"]);

				return;
			}
		}
		echo "Faded in total of " . (microtime(true) - $beginSec) . " seconds\n";

		foreach($pool as $arduino)
			$arduino->sendColorArray($rgb);
		$this->status->setCurrentColor($rgb);
	}

	/**
	 * @param array $color1
	 * @param array $color2
	 *
	 * @return array
	 */
	protected function getShades(array $color1, array $color2) : array{
		$shades = array();
		for($i = 0; $i <= 100; $i++) $shades[$i] = Color::mixColors($color1, $color2, $i);

		return $shades;
	}

	/**
	 * @param array $shades
	 * @param float $seconds
	 *
	 * @return array
	 */
	protected function mapShadesToSeconds(array $shades, float $seconds) : array{
		$secondStep = $seconds / 100;
		$steppedShades = array();
		foreach($shades as $i => $shade){
			$steppedShades[] = ["time" => $secondStep * $i, "shade" => $shade];
		}

		return $steppedShades;
	}

	/**
	 * @param array $steppedShades
	 * @param float $minSeconds
	 *
	 * @return array
	 */
	protected function adjustSteppedShades(array $steppedShades, float $minSeconds = 0.00025) : array{
		$lastTime = 0;
		foreach($steppedShades as $i => $shade){
			if($shade["time"] - $lastTime < $minSeconds) unset($steppedShades[$i]);
			else $lastTime = $shade["time"];
		}

		return $steppedShades;
	}

}