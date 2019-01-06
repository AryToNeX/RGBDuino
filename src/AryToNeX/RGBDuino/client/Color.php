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

use AryToNeX\RGBDuino\client\color\ColorUtils;

/**
 * Class Color
 * @package AryToNeX\RGBDuino\client
 */
class Color{

	/** @var int */
	protected $r;
	/** @var int */
	protected $g;
	/** @var int */
	protected $b;

	/**
	 * Color constructor.
	 *
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 */
	public function __construct(int $r, int $g, int $b){
		$this->r = $r;
		$this->g = $g;
		$this->b = $b;
	}

	/**
	 * @param float $minSaturation
	 * @param float $minLuminance
	 */
	public function sanitize(float $minSaturation = 0.75, float $minLuminance = 0.50) : void{
		if($this->r == $this->g && $this->g == $this->b) if($this->r > 64){
			$this->r = 255;
			$this->g = 255;
			$this->b = 255;

			return;
		}else{
			$this->r = 0;
			$this->g = 0;
			$this->b = 0;

			return;
		}

		$hsv = color\ColorUtils::fromRgbToHsv($this->asArray());

		$hsv["s"] = ($hsv["s"] < $minSaturation ? $minSaturation : $hsv["s"]);
		$hsv["v"] = ($hsv["v"] < $minLuminance ? $minLuminance : $hsv["v"]);

		$rgb = color\ColorUtils::fromHsvToRgb($hsv);

		$this->r = $rgb["r"];
		$this->g = $rgb["g"];
		$this->b = $rgb["b"];
	}

	/**
	 * @param Color $color
	 *
	 * @return bool
	 */
	public function equals(Color $color) : bool{
		return (
			$this->r == $color->getR() &&
			$this->g == $color->getG() &&
			$this->b == $color->getB()
		);
	}

	/**
	 * @return int
	 */
	public function getR() : int{
		return $this->r;
	}

	/**
	 * @param int $r
	 */
	public function setR(int $r) : void{
		$this->r = $r;
	}

	/**
	 * @return int
	 */
	public function getG() : int{
		return $this->g;
	}

	/**
	 * @param int $g
	 */
	public function setG(int $g) : void{
		$this->g = $g;
	}

	/**
	 * @return int
	 */
	public function getB() : int{
		return $this->b;
	}

	/**
	 * @param int $b
	 */
	public function setB(int $b) : void{
		$this->b = $b;
	}

	/**
	 * @return array
	 */
	public function asArray() : array{
		return ["r" => $this->r, "g" => $this->g, "b" => $this->b];
	}

	/**
	 * @return string
	 */
	public function asHex() : string{
		return color\ColorUtils::fromRgbToHex($this->asArray(), false);
	}

	/**
	 * @param string $hex
	 *
	 * @return Color
	 */
	public static function fromHex(string $hex) : self{
		return self::fromArray(ColorUtils::fromHexToRgb($hex));
	}

	/**
	 * @param array $rgb
	 *
	 * @return Color
	 */
	public static function fromArray(array $rgb) : self{
		return new self($rgb["r"], $rgb["g"], $rgb["b"]);
	}

}