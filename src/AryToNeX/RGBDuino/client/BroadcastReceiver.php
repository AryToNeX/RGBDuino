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
	/** @var resource */
	protected $sock;

	/**
	 * BroadcastReceiver constructor.
	 *
	 * @param Status $status
	 * @param int    $port
	 */
	public function __construct(Status $status, int $port){
		$this->status = $status;
		$this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_nonblock($this->sock);
		socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->sock, "0.0.0.0", $port);
		$this->tries = 0;
	}

	public function receiveBroadcast(){
		$msg = $this->message_parse();
		if(isset($msg)){
				echo "Broadcast received from server; connecting...\n";
				if($this->connectToServer($msg["ip"], $msg["port"]))
					echo "Connected to server!\n";
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
	 * @param int    $timeout
	 *
	 * @return array
	 */
	protected function message_parse($timeout = 5) : ?array{
		$data = "";
		$preTime = time();
		do{
			socket_recvfrom($this->sock, $buf, 100, 0, $ip, $port);
			$data .= $buf;
			if(time() - $preTime > $timeout) break;
		}while($buf === null);

		$msg = json_decode($data, true);
		if(!empty($msg) && isset($msg["port"])){
			$msg["ip"] = $ip;

			return $msg;
		}

		return null;
	}

}