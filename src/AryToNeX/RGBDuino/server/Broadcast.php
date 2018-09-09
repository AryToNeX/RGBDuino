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
 * Class Broadcast
 * @package AryToNeX\RGBDuino\server
 */
class Broadcast{

	/**
	 * @var Status
	 */
	protected $status;
	/**
	 * @var int
	 */
	protected $port;
	/**
	 * @var array
	 */
	protected $networks;
	/**
	 * @var int
	 */
	protected $lastTime;
	/**
	 * @var resource
	 */
	protected $sock;

	/**
	 * Broadcast constructor.
	 *
	 * @param Status $status
	 * @param int    $port
	 */
	public function __construct(Status $status, int $port){
		$this->status = $status;
		$this->port = $port;
		$this->networks = self::getIpArray();
		$this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_option($this->sock, SOL_SOCKET, SO_BROADCAST, 1);
		socket_set_nonblock($this->sock);
		$this->broadcast();
	}

	/**
	 * @return bool
	 */
	public function isTimeToBroadcast() : bool{
		$time = $this->status->getConfig()->getValue("broadcastEvery") ?? 5;
		if($time < 1) $time = 1;
		if($time > 30) $time = 30;

		return time() - $this->lastTime > $time;
	}

	public function broadcast() : void{
		foreach($this->networks as $network){
			$broadcastIP = self::getBroadcastIP($network["ip"], $network["mask"]);
			$msg = array(
				"_"    => "rgbbroadcast",
				"ip"   => $network["ip"],
				"port" => $this->status->getTcpManager()->getPort(),
			);

			$msg = json_encode($msg);
			socket_sendto($this->sock, $msg, strlen($msg), 0, $broadcastIP, $this->port);
		}
		$this->lastTime = time();
	}

	/**
	 * @return array
	 */
	protected static function getIpArray() : array{
		exec("ifconfig", $out);
		$out = implode("\n", $out);
		$out = explode("\n\n", $out);
		$networks = array();
		foreach($out as $str){
			/** @var string $str */
			preg_match_all("/([a-zA-Z0-9]+): flags|inet ([0-9.]+)  |netmask ([0-9.]+)/", $str, $matches);

			// get rid of irrelevant things
			array_shift($matches);

			// check IP
			if(empty($matches[1][1]) || $matches[1][1] == "127.0.0.1") continue;

			// map
			$network = array(
				"card" => $matches[0][0],
				"ip"   => $matches[1][1],
				"mask" => $matches[2][2],
			);
			$networks[] = $network;
		}

		return $networks;
	}

	/**
	 * @param string $ip
	 * @param string $mask
	 *
	 * @return string
	 */
	public static function getBroadcastIP(string $ip, string $mask) : string{
		// Broadcast is a logical OR between the IP address and the wildcard mask
		return long2ip(
			ip2long($ip) | (~ip2long($mask))
		);
	}

}