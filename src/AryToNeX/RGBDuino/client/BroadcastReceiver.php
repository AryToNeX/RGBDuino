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

use AryToNeX\RGBDuino\client\exceptions\MalformedIPException;
use AryToNeX\RGBDuino\client\exceptions\UnresponsiveServerException;

/**
 * Class BroadcastReceiver
 * @package AryToNeX\RGBDuino\client
 */
class BroadcastReceiver{

	/** @var Status */
	protected $status;
	/** @var array */
	protected $networks;
	/** @var resource */
	protected $sock;
	/** @var int */
	protected $tries;

	/**
	 * BroadcastReceiver constructor.
	 *
	 * @param Status $status
	 * @param int    $port
	 */
	public function __construct(Status $status, int $port){
		$this->status = $status;
		$this->networks = self::getIpArray();
		$this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_nonblock($this->sock);
		socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->sock, "0.0.0.0", $port);
		$this->tries = 0;
	}

	public function receiveBroadcast(){
		$msg = json_decode(self::socket_read($this->sock), true);
		if(!empty($msg)){
			if($msg["_"] == "rgbrecheck"){
				$isClient = false;
				foreach($this->networks as $network){
					if($msg["lastclient"] === $network["ip"]){
						$isClient = true;
						break;
					}
				}

				if($isClient){
					$this->status->getCommunicator()->sendClientIsHere();
					echo "Broadcast received from server; client is still here.\n";
				}else
					$this->tries++;

				if($this->tries >= 5){
					echo "Broadcast received from server; a client abandoned it, assuming it's safe to connect.\n";
					if($this->connectToServer($msg["ip"], $msg["port"]))
						$this->tries = 0;
				}
			}else{
				echo "Broadcast received from server; connecting...\n";
				if($this->connectToServer($msg["ip"], $msg["port"]))
					$this->tries = 0;
			}
		}
	}

	public function connectToServer(string $ip, int $port) : bool{
		try{
			$this->status->setCommunicator(new Communicator($ip, $port));
		}catch(MalformedIPException | UnresponsiveServerException $e){
			$this->status->setCommunicator(null);

			return false;
		}
		$this->status->getConfig()->setValue("serverIp", $ip);
		$this->status->getConfig()->setValue("serverPort", $port);
		$this->status->getConfig()->save();

		return true;
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
	 * @param        $sock
	 * @param int    $timeout
	 *
	 * @return string
	 */
	protected static function socket_read($sock, $timeout = 5) : string{
		$data = "";
		$preTime = time();
		do{
			socket_recv($sock, $buf, 100, 0);
			$data .= $buf;
			if(time() - $preTime > $timeout) break;
		}while($buf === null);

		return $data;
	}

}