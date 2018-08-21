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

/**
 * Class TCPCommandsManager
 *
 * @package AryToNeX\RGBDuino
 */
class TCPCommandsManager{

	/** @var resource */
	private $sock;
	/** @var Status */
	private $owner;

	/**
	 * TCPCommandsManager constructor.
	 *
	 * @param Status $owner
	 * @param int    $port
	 */
	public function __construct(Status $owner, int $port){
		$this->owner = $owner;
		$this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_nonblock($this->sock);
		socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->sock, "0.0.0.0", $port);
		socket_listen($this->sock);
		echo "TCP Socket listening on port $port\n";
	}

	public function doStuff() : void{
		$accept = socket_accept($this->sock);
		if(is_resource($accept)){
			// let's do some stuff yay
			socket_getpeername($accept, $ip, $port);
			echo "TCP: Connection from $ip:$port. Serving...\n";
			$str = self::socket_read_until($accept, "\n");
			$str = explode(" ", $str);
			switch($str[0]){
				case "setColor":
					if(!isset($str[1])){
						socket_write($accept, "COLOR_ERROR\n");
						echo "TCP: Custom color was not set\n";
						break;
					}
					$this->owner->setUserChosenColor(color\Color::fromHexToRgb($str[1]));
					socket_write($accept, "COLOR_SET\n");
					echo "TCP: Set custom color to {$str[1]}\n";
					break;
				case "unsetColor":
					$this->owner->setUserChosenColor(null);
					socket_write($accept, "COLOR_UNSET\n");
					echo "TCP: Unset custom color\n";
					break;
				case "saveColor":
					$this->owner->getArduino()->saveDisplayedColor();
					socket_write($accept, "COLOR_SAVED\n");
					echo "TCP: Color saved to EEPROM\n";
					break;
				case "listPlayers":
					$pl = $this->owner->getPlayerStatus()->getPlayerCtl()->getPlayers();
					socket_write($accept, json_encode($pl) . "\n");
					echo "TCP: Requested players list\n";
					break;
				case "setPlayer":
					if(!isset($str[1])){
						socket_write($accept, "PLAYER_ERROR\n");
						echo "TCP: Player was not set\n";
						break;
					}
					$pl = $this->owner->getPlayerStatus()->getPlayerCtl()->getPlayers();
					if(in_array($str[1], $pl)){
						$this->owner->getPlayerStatus()->getPlayerCtl()->setActivePlayer($str[1]);
						socket_write($accept, "PLAYER_SET\n");
						echo "TCP: Player set to {$str[1]}\n";
					}else{
						socket_write($accept, "PLAYER_ERROR\n");
						echo "TCP: Player {$str[1]} not found\n";
					}
					break;
				case "restart":
					socket_write($accept, "RESTARTING\n");
					echo "TCP: Restarting...\n";
					$this->owner->setShouldExit(2);
					break;
				case "stop":
					socket_write($accept, "STOPPING\n");
					echo "TCP: Stopping...\n";
					$this->owner->setShouldExit(1);
					break;
				default:
					socket_write($accept, "UNDEFINED_COMMAND\n");
					echo "TCP: Undefined command\n";
					break;
			}
			if($str[0] !== "") socket_shutdown($accept, 2);
			socket_close($accept);
			echo "TCP: Connection from $ip:$port closed.\n";
		}
	}

	public function close() : void{
		$linger = array('l_linger' => 0, 'l_onoff' => 1);
		socket_set_option($this->sock, SOL_SOCKET, SO_LINGER, $linger);
		socket_shutdown($this->sock);
		socket_close($this->sock);
	}

	/**
	 * @param        $sock
	 * @param string $str
	 * @param bool   $or_until_data_finish
	 * @param int    $timeout
	 *
	 * @return string
	 */
	protected static function socket_read_until($sock,
												string $str,
												bool $or_until_data_finish = true,
												int $timeout = 5) : string{
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

		return $data;
	}
}