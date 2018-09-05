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

namespace AryToNeX\RGBDuino\cli;

use AryToNeX\RGBDuino\cli\exceptions\TCPSocketException;
use AryToNeX\RGBDuino\cli\exceptions\MalformedIPException;

/**
 * Class ServerCommunicator
 * @package AryToNeX\RGBDuino\cli
 */
class ServerCommunicator{

	/** @var string */
	private $ip;
	/** @var int */
	private $port;

	/**
	 * @param null|string $cfgDir
	 *
	 * @return ServerCommunicator
	 * @throws MalformedIPException
	 */
	public static function fromClientConfig(?string $cfgDir = null) : self{
		$config = json_decode(
			file_get_contents(
				$cfgDir ??
				"/home/" . exec("whoami") . "/.local/share/RGBDuino-Client/config.json"
			),
			true
		);

		return new self($config["serverIp"], $config["serverPort"]);
	}

	/**
	 * @param string $ip
	 * @param int    $port
	 *
	 * @return ServerCommunicator
	 * @throws MalformedIPException
	 */
	public static function fromIP(string $ip, int $port = 6969) : self{
		return new self($ip, $port);
	}

	/**
	 * ServerCommunicator constructor.
	 *
	 * @param string $ip
	 * @param int    $port
	 *
	 * @throws MalformedIPException
	 */
	protected function __construct(string $ip, int $port){
		$this->ip = filter_var($ip, FILTER_VALIDATE_IP);
		if($this->ip === false) throw new MalformedIPException("Malformed IP!");
		$this->port = $port;
	}

	/**
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @return string
	 */
	public function __call(string $name, array $arguments) : string{
		try{
			$sock = $this->createSock();
		}catch(TCPSocketException $e){
			return false;
		}
		$response = self::writeCommand($sock, array_merge([$name], $arguments));

		return $response;
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
	 * @return array|null
	 */
	public function getDevices() : ?array{
		try{
			$sock = $this->createSock();
		}catch(TCPSocketException $e){
			return null;
		}

		$response = self::writeCommand($sock, ["getDevices"]);

		return json_decode($response, true);
	}

	/**
	 * @param string $identifier
	 * @param bool   $state
	 *
	 * @return bool
	 */
	public function setDevice(string $identifier, bool $state) : bool{
		try{
			$sock = $this->createSock();
		}catch(TCPSocketException $e){
			return null;
		}

		$response = self::writeCommand($sock, ["setDevice", $identifier, ($state === true ? "on" : "off")]);

		if($response == "SETDEVICE_SUCCESS") return true;

		return false;
	}

	/**
	 * @return bool
	 */
	public function update() : bool{
		try{
			$sock = $this->createSock();
		}catch(TCPSocketException $e){
			return null;
		}

		$response = self::writeCommand($sock, ["update"]);

		if($response == "UPDATE_SUCCESS") return true;

		return false;
	}

	/**
	 * @param null|string $hex
	 *
	 * @return bool
	 */
	public function setColor(?string $hex) : bool{
		try{
			$sock = $this->createSock();
		}catch(TCPSocketException $e){
			return false;
		}

		if(!isset($hex)){
			$response = self::writeCommand($sock, ["unsetColor"]);
			if($response === "COLOR_UNSET") return true;

			return false;
		}

		$response = self::writeCommand($sock, ["setColor", $hex]);
		if($response === "COLOR_SET") return true;

		return false;
	}

	/**
	 * @return bool
	 */
	public function saveColor() : bool{
		try{
			$sock = $this->createSock();
		}catch(TCPSocketException $e){
			return false;
		}

		$response = self::writeCommand($sock, ["saveColor"]);
		if($response === "COLOR_SAVED") return true;

		return false;
	}

	/**
	 * @return bool
	 */
	public function stopServer() : bool{
		try{
			$sock = $this->createSock();
		}catch(TCPSocketException $e){
			return false;
		}

		$response = self::writeCommand($sock, ["stop"]);
		if($response === "STOPPING") return true;

		return false;
	}

	/**
	 * @return bool
	 */
	public function restartServer() : bool{
		try{
			$sock = $this->createSock();
		}catch(TCPSocketException $e){
			return false;
		}

		$response = self::writeCommand($sock, ["restart"]);
		if($response === "RESTARTING") return true;

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
	protected static function close($sock) : void{
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