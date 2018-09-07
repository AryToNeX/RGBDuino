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

use AryToNeX\RGBDuino\client\exceptions\TCPSocketException;
use AryToNeX\RGBDuino\client\exceptions\MalformedIPException;

/**
 * Class Communicator
 * @package AryToNeX\RGBDuino\client
 */
class Communicator{

	/** @var string */
	private $ip;
	/** @var int */
	private $port;

	/**
	 * Communicator constructor.
	 *
	 * @param string $ip
	 * @param int    $port
	 *
	 * @throws MalformedIPException
	 */
	public function __construct(string $ip, int $port){
		$this->ip = filter_var($ip, FILTER_VALIDATE_IP);
		if($this->ip === false) throw new MalformedIPException("Malformed IP!");
		$this->port = $port;
	}

	/**
	 * @return bool
	 */
	public function tryPing() : bool{
		try{
			$sock = $this->createSock();
		}catch(TCPSocketException $e){
			return false;
		}
		$response = self::writeCommand($sock, ["ping"]);
		if($response === "PONG") return true;

		return false;
	}

	/**
	 * @param array|null $color
	 *
	 * @return bool
	 */
	public function sendWallpaperColor(?Color $color) : bool{
		try{
			$sock = $this->createSock();
		}catch(TCPSocketException $e){
			return false;
		}
		if(!isset($color)){
			$response = self::writeCommand($sock, ["unsetWallpaperColor"]);
			if($response === "WPCOLOR_UNSET") return true;

			return false;
		}
		$response = self::writeCommand(
			$sock,
			[
				"setWallpaperColor",
				$color->asHex(),
			]
		);
		if($response === "WPCOLOR_SET") return true;

		return false;
	}

	/**
	 * @param PlayerDetails $playerDetails
	 *
	 * @return bool
	 */
	public function sendPlayerDetails(PlayerDetails $playerDetails) : bool{
		try{
			$sock = $this->createSock();
		}catch(TCPSocketException $e){
			return false;
		}
		$playerDetails = $playerDetails->toHash();
		$response = self::writeCommand(
			$sock,
			[
				"setPlayerDetails",
				$playerDetails,
			]
		);
		if($response === "PLAYER_DETAILS_SET") return true;

		return false;
	}

	/**
	 * @return resource
	 * @throws TCPSocketException
	 */
	protected function createSock(){
		if(!$sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP))
			throw new TCPSocketException("Couldn't create socket!\n");
		if(!@socket_connect($sock, $this->ip, $this->port))
			throw new TCPSocketException("Couldn't connect to socket!\n");

		return $sock;
	}

	/**
	 * @param $sock
	 */
	protected static function close($sock){
		$linger = array('l_linger' => 1, 'l_onoff' => 1);
		socket_set_option($sock, SOL_SOCKET, SO_LINGER, $linger);
		socket_shutdown($sock);
		socket_close($sock);
	}

	/**
	 * @param       $sock
	 * @param array $args
	 *
	 * @return string
	 */
	protected static function writeCommand($sock, array $args) : string{
		socket_write($sock, implode(" ", $args) . "\n");
		$response = self::socket_read_until($sock, "\n");

		return $response;
	}

	/**
	 * @param        $sock
	 * @param string $str
	 * @param bool   $or_until_data_finish
	 * @param int    $timeout
	 *
	 * @return string
	 */
	protected static function socket_read_until(
		$sock,
		string $str,
		bool $or_until_data_finish = true,
		int $timeout = 5
	) : string{
		$data = "";
		$buf = "";
		$preTime = time();
		while(true){
			$by = socket_recv($sock, $buf, 1, MSG_DONTWAIT);
			// if char reached break
			if($buf === $str) break;

			// if remote disconnects break
			if($by === 0) break;

			// if connection timeouts break
			if(time() - $preTime > $timeout) break;

			// if data finishes break
			if($or_until_data_finish && $buf === null){
				if($data !== "") break;
			}

			// add buffer to data string
			$data .= $buf;
		}

		self::close($sock);

		return $data;
	}

}