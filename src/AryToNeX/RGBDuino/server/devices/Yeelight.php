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

use AryToNeX\RGBDuino\server\color\ColorUtils;
use AryToNeX\RGBDuino\server\exceptions\MalformedIPException;

/**
 * Class Yeelight
 * @package AryToNeX\RGBDuino\server\devices
 */
class Yeelight extends Device{

	const YEELIGHT_PORT = 55443;

	/** @var mixed */
	protected $ip;
	/** @var resource */
	protected $sock;

	/**
	 * Yeelight constructor.
	 *
	 * @param string $identifier
	 * @param string $ip
	 *
	 * @throws MalformedIPException
	 */
	public function __construct(string $identifier, string $ip){
		parent::__construct($identifier);
		$this->ip = filter_var($ip, FILTER_VALIDATE_IP);
		if($this->ip === false) throw new MalformedIPException("Malformed IP!");
		$this->setActive(true);
	}

	/**
	 * @return string
	 */
	public function getIp() : string{
		return $this->ip;
	}

	/**
	 * @return bool
	 */
	public function isConnected() : bool{
		$result = $this->connect();
		$this->close();

		return $result;
	}

	/**
	 * @param bool $isActive
	 */
	public function setActive(bool $isActive) : void{
		if($isActive) $this->sendData(
			json_encode(
				array(
					"id"     => 0,
					"method" => "set_power",
					"params" => ["on", "sudden", 30, 2],
				)
			)
		);
		else $this->sendData(
			json_encode(
				array(
					"id"     => 0,
					"method" => "set_power",
					"params" => ["off", "sudden", 30, 2],
				)
			)
		);
		parent::setActive($isActive);
	}

	/**
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 */
	public function sendColorValues(int $r, int $g, int $b) : void{
		parent::sendColorValues($r, $g, $b);
		$val = array(
			"id"     => 1,
			"method" => "set_rgb",
			"params" => [ColorUtils::fromRgbToInt(["r" => $r, "g" => $g, "b" => $b]), "sudden", 30],
		);
		$this->sendData(json_encode($val));
	}

	public function saveDisplayedColor() : void{
		$val = array(
			"id"     => 1,
			"method" => "set_default",
			"params" => [],
		);
		$this->sendData(json_encode($val));
	}

	public function sendFadeColorValues(int $r, int $g, int $b, float $seconds){
		parent::sendColorValues($r, $g, $b);
		$val = array(
			"id"     => 1,
			"method" => "set_rgb",
			"params" => [ColorUtils::fromRgbToInt(["r" => $r, "g" => $g, "b" => $b]), "smooth", $seconds * 1000],
		);
		$this->sendData(json_encode($val));
	}

	/**
	 * @param string $data
	 */
	protected function sendData(string $data) : void{
		$this->sendDataOnly($data);
		$this->close();
	}

	protected function sendDataOnly(string $data) : void{
		if(!$this->connect()) return;
		fwrite($this->sock, $data . "\r\n");
		fflush($this->sock);
	}

	protected function sendDataAndReturn(string $data) : ?array{
		$this->sendData($data);
		if(!isset($this->sock)) return null;
		usleep(100 * 1000);
		$out = array();
		$out[] = fgets($this->sock);
		$this->close();

		return $out;
	}

	public function close() : void{
		if($this->sock === null) return;
		fclose($this->sock);
		$this->sock = null;
	}

	protected function connect() : bool{
		$this->sock = fsockopen($this->ip, 55443, $errno, $errstr, 5);
		if(!$this->sock || !isset($this->sock)){
			$this->sock = null;

			return false;
		}
		stream_set_blocking($this->sock, false);

		return true;
	}

	protected function computeIdentifier() : void{
		return; // Nothing for now
	}
}