<?php

namespace AryToNeX\RGBDuino\color;

/**
 * Class Color
 * @package AryToNeX\RGBDuino\color
 */
class Color{

	/**
	 * @param array $rgb1
	 * @param array $rgb2
	 * @param int   $percentage
	 *
	 * @return array
	 */
	public static function mixColors(array $rgb1, array $rgb2, int $percentage) : array{
		$percentage = $percentage / 100;
		$newRgb = array();
		$newRgb["r"] = intval(round($rgb2["r"] * $percentage + $rgb1["r"] * (1 - $percentage)));
		$newRgb["g"] = intval(round($rgb2["g"] * $percentage + $rgb1["g"] * (1 - $percentage)));
		$newRgb["b"] = intval(round($rgb2["b"] * $percentage + $rgb1["b"] * (1 - $percentage)));

		return $newRgb;
	}

	/**
	 * @param array $rgb
	 *
	 * @return array
	 */
	public static function fromRgbToHsv(array $rgb) : array{
		list($r, $g, $b) = array_values($rgb);
		$var_R = ($r / 255);
		$var_G = ($g / 255);
		$var_B = ($b / 255);

		$var_Min = min($var_R, $var_G, $var_B);
		$var_Max = max($var_R, $var_G, $var_B);
		$del_Max = $var_Max - $var_Min;

		$v = $var_Max;

		if($del_Max == 0){
			$h = 0;
			$s = 0;
		}else{
			$s = $del_Max / $var_Max;

			$del_R = ((($var_Max - $var_R) / 6) + ($del_Max / 2)) / $del_Max;
			$del_G = ((($var_Max - $var_G) / 6) + ($del_Max / 2)) / $del_Max;
			$del_B = ((($var_Max - $var_B) / 6) + ($del_Max / 2)) / $del_Max;

			if($var_R == $var_Max) $h = $del_B - $del_G;
			else if($var_G == $var_Max) $h = (1 / 3) + $del_R - $del_B;
			else if($var_B == $var_Max) $h = (2 / 3) + $del_G - $del_R;

			if($h < 0) $h++;
			if($h > 1) $h--;
		}

		return array("h" => $h, "s" => $s, "v" => $v);
	}

	/**
	 * @param array $hsv
	 *
	 * @return array
	 */
	public static function fromHsvToRgb(array $hsv) : array{
		list($h, $s, $v) = array_values($hsv);
		if($s == 0){
			$r = $g = $B = $v * 255;
		}else{
			$var_H = $h * 6;
			$var_i = floor($var_H);
			$var_1 = $v * (1 - $s);
			$var_2 = $v * (1 - $s * ($var_H - $var_i));
			$var_3 = $v * (1 - $s * (1 - ($var_H - $var_i)));

			if($var_i == 0){
				$var_R = $v;
				$var_G = $var_3;
				$var_B = $var_1;
			}else if($var_i == 1){
				$var_R = $var_2;
				$var_G = $v;
				$var_B = $var_1;
			}else if($var_i == 2){
				$var_R = $var_1;
				$var_G = $v;
				$var_B = $var_3;
			}else if($var_i == 3){
				$var_R = $var_1;
				$var_G = $var_2;
				$var_B = $v;
			}else if($var_i == 4){
				$var_R = $var_3;
				$var_G = $var_1;
				$var_B = $v;
			}else{
				$var_R = $v;
				$var_G = $var_1;
				$var_B = $var_2;
			}

			$r = $var_R * 255;
			$g = $var_G * 255;
			$B = $var_B * 255;
		}

		return array("r" => $r, "g" => $g, "b" => $B);
	}

	/**
	 * @param array $rgb
	 * @param bool  $prependHash = false
	 *
	 * @return string
	 */
	public static function fromRgbToHex(array $rgb, bool $prependHash = false) : string{
		list($r, $g, $b) = array_values($rgb);

		return ($prependHash ? '#' : '') .
			str_pad(dechex($r), 2, "0", STR_PAD_LEFT) .
			str_pad(dechex($g), 2, "0", STR_PAD_LEFT) .
			str_pad(dechex($b), 2, "0", STR_PAD_LEFT);
	}

	/**
	 * @param string $hex
	 *
	 * @return array
	 */
	public static function fromHexToRgb(string $hex) : array{
		$hex = strtolower(substr(str_replace("#", "", trim($hex)), 0, 6));
		if(preg_match("/^[0-9a-f]+$/", $hex) !== 1) return array(
			"r" => 0,
			"g" => 0,
			"b" => 0,
		); // default to black for invalid color
		$rgb = str_split($hex, 2);
		for($i = 0; $i < 3; $i++){
			if(!isset($rgb[$i])) $rgb[$i] = "00";
			$rgb[$i] = hexdec($rgb[$i]);
		}
		list($r, $g, $b) = array_values($rgb);

		return array("r" => $r, "g" => $g, "b" => $b);
	}

	/**
	 * @param int  $color
	 * @param bool $prependHash = false
	 *
	 * @return string
	 */
	public static function fromIntToHex(int $color, bool $prependHash = false) : string{
		return ($prependHash ? '#' : '') . sprintf('%06X', $color);
	}

	/**
	 * @param string $color
	 *
	 * @return int
	 */
	public static function fromHexToInt(string $color) : int{
		return hexdec(ltrim($color, '#'));
	}

	/**
	 * @param int $color
	 *
	 * @return array
	 */
	public static function fromIntToRgb(int $color) : array{
		return [
			'r' => $color >> 16 & 0xFF,
			'g' => $color >> 8 & 0xFF,
			'b' => $color & 0xFF,
		];
	}

	/**
	 * @param array $components
	 *
	 * @return int
	 */
	public static function fromRgbToInt(array $components) : int{
		return ($components['r'] * 65536) + ($components['g'] * 256) + ($components['b']);
	}
}
