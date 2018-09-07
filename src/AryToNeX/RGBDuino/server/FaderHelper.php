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

use AryToNeX\RGBDuino\server\color\ColorUtils;

/**
 * Class FaderHelper
 *
 * @package AryToNeX\RGBDuino\server
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
	 * @param Color         $color
	 * @param callable|null $shouldStop
	 * @param bool          $priority
	 */
	public function fadeTo(Color $color, ?callable $shouldStop = null, bool $priority = false) : void{

		$pool = $this->status->getDevicePool()->toArray();

		foreach($pool as $id => $device){
			if(!$device->isActive()){
				unset($pool[$id]);
				continue;
			}

			$testColor = $color;
			if(!$priority){
				if(!is_null($this->status->getUserChosenColor($id))){
					$testColor = $this->status->getUserChosenColor($id);
				}
			}
			if($device->getCurrentColor()->equals($testColor)){
				unset($pool[$id]);
			}
		}
		if(empty($pool)) return;

		$shades = array();
		foreach($pool as $id => $device){
			if(!$priority){
				if(!is_null($this->status->getUserChosenColor($id))){
					$shades[$id] = $this->getShades($device->getCurrentColor(), $this->status->getUserChosenColor($id));
					continue;
				}
			}
			$shades[$id] = $this->getShades($device->getCurrentColor(), $color);
		}

		for($i = 0; $i <= 100; $i++){
			usleep(20000);
			foreach($pool as $id => $device)
				$device->sendColor($shades[$id][$i]);

			if(isset($shouldStop) and $shouldStop()) return;
		}
	}

	/**
	 * @param Color         $color
	 * @param float         $seconds
	 * @param callable|null $shouldStop
	 * @param bool          $priority
	 */
	public function timedFadeTo(Color $color,
								float $seconds = 2,
								?callable $shouldStop = null,
								bool $priority =
								false) : void{

		$pool = $this->status->getDevicePool()->toArray();

		foreach($pool as $id => $device){
			if(!$device->isActive()){
				unset($pool[$id]);
				continue;
			}

			$testColor = $color;
			if(!$priority){
				if(!is_null($this->status->getUserChosenColor($id))){
					$testColor = $this->status->getUserChosenColor($id);
				}
			}

			if($device->getCurrentColor()->equals($testColor)){
				unset($pool[$id]);
			}
		}
		if(empty($pool)) return;

		$shades = array();
		$finishedColors = array();
		foreach($pool as $id => $device){
			if(!$priority){
				if(!is_null($this->status->getUserChosenColor($id))){
					$shades[$id] = $this->getShades($device->getCurrentColor(), $this->status->getUserChosenColor($id));
					$finishedColors[$id] = $this->status->getUserChosenColor($id);
					continue;
				}
			}
			$shades[$id] = $this->getShades($device->getCurrentColor(), $color);
			$finishedColors[$id] = $color;
		}

		$shades = $this->mapShadesToSeconds($shades, $seconds);

		$beginSec = microtime(true);
		foreach($shades as $shade){
			$nowSec = microtime(true) - $beginSec;

			while($shade["time"] > $nowSec){
				$nowSec = microtime(true) - $beginSec;
				usleep(1);
			}

			foreach($pool as $id => $device)
				$device->sendColor($shade["shade"][$id]);

			if(isset($shouldStop) and $shouldStop()) return;
		}

		foreach($pool as $id => $device)
			$device->sendColor($finishedColors[$id]);

		echo "Faded in total of " . (microtime(true) - $beginSec) . " seconds\n";
	}

	/**
	 * @param Color $color1
	 * @param Color $color2
	 *
	 * @return Color[]
	 */
	protected function getShades(Color $color1, Color $color2) : array{
		$shades = array();
		for($i = 0; $i <= 100; $i++)
			$shades[$i] = Color::fromArray(ColorUtils::mixColors($color1->asArray(), $color2->asArray(), $i));

		return $shades;
	}

	/**
	 * @param array $idShades
	 * @param float $seconds
	 *
	 * @return Color[]
	 */
	protected function mapShadesToSeconds(array $idShades, float $seconds) : array{
		$secondStep = $seconds / 100;
		$steppedShades = array();
		for($i = 0; $i <= 100; $i++){
			$steppedShade = array();
			$steppedShade["time"] = $secondStep * $i;
			$steppedShade["shade"] = array();
			foreach($idShades as $id => $shades)
				$steppedShade["shade"][$id] = $shades[$i];

			$steppedShades[] = $steppedShade;
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