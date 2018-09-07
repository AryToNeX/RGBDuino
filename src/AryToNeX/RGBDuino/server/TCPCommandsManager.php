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
 * Class TCPCommandsManager
 *
 * @package AryToNeX\RGBDuino\server
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
			switch(array_shift($str)){
				case "ping":
					socket_write($accept, "PONG\n");
					echo "TCP: Received internal ping\n";
					break;
				case "setColor":
					if(!isset($str[0])){
						socket_write($accept, "COLOR_ERROR\n");
						echo "TCP: Custom color was not set\n";
						break;
					}
					$this->owner->setUserChosenColor(Color::fromHex($str[0]), $str[1] ?? null);
					socket_write($accept, "COLOR_SET\n");
					echo "TCP: Set custom color to {$str[0]} in device " . ($str[1] ?? "global") . "\n";
					break;
				case "unsetColor":
					$this->owner->setUserChosenColor(null, $str[0] ?? null);
					socket_write($accept, "COLOR_UNSET\n");
					echo "TCP: Unset custom color in device " . ($str[0] ?? "global") . "\n";
					break;
				case "setWallpaperColor":
					if(!isset($str[0])){
						socket_write($accept, "COLOR_ERROR\n");
						echo "TCP: Wallpaper color was not set\n";
						break;
					}
					$this->owner->setWallpaperColor(Color::fromHex($str[0]));
					$this->owner->setWallpaperChanged(true);
					socket_write($accept, "WPCOLOR_SET\n");
					echo "TCP: Set wallpaper color to {$str[0]}\n";
					break;
				case "unsetWallpaperColor":
					$this->owner->setWallpaperColor(null);
					$this->owner->setWallpaperChanged(true);
					socket_write($accept, "WPCOLOR_UNSET\n");
					echo "TCP: Unset wallpaper color\n";
					break;
				case "saveColor":
					if(isset($str[0]) && $str[0] !== "global"){
						if(is_null($this->owner->getDevicePool()->get($str[0]))){
							socket_write($accept, "COLOR_SAVE_ERROR\n");
							echo "TCP: Device $str[0] not found\n";
							break;
						}
						$this->owner->getDevicePool()->get($str[0])->saveDisplayedColor();
					}else{
						foreach($this->owner->getDevicePool()->toArray() as $device)
							$device->saveDisplayedColor();
					}
					socket_write($accept, "COLOR_SAVED\n");
					echo "TCP: Color of device " . ($str[0] ?? "global") . " saved\n";
					break;
				case "setPlayerDetails":
					if($this->owner->getPlayerStatus() === null){
						echo "TCP: Attempt to set player details discarded; not enabled in config\n";
						socket_write($accept, "PLAYER_DETAILS_NOT_ENABLED\n");
						break;
					}

					$json = base64_decode(trim(implode(" ", $str)));
					if(!isset($json) || $json === ""){
						socket_write($accept, "PLAYER_DETAILS_ERROR\n");
						echo "TCP: Player details were not set\n";
						break;
					}

					$json = json_decode($json, true);
					if(!isset($json["playing"])){
						socket_write($accept, "PLAYER_DETAILS_ERROR\n");
						echo "TCP: Player details were not set\n";
						break;
					}
					$this->owner->getPlayerStatus()->setPlaying($json["playing"]);
					if($json["playing"] && !isset($json["url"])){
						socket_write($accept, "PLAYER_DETAILS_ERROR\n");
						echo "TCP: Player details were not set\n";
						break;
					}
					$this->owner->getPlayerStatus()->setArtURL($json["url"]);


					if(isset($json["colors"]) && !empty($json["colors"])){
						$colors = array();

						foreach($json["colors"] as $rgb){
							$colors[] = Color::fromArray($rgb);
						}

						$this->owner->getPlayerStatus()->setAlbumArtColorArray($colors);
					}else $this->owner->getPlayerStatus()->setAlbumArtColorArray(null);
					socket_write($accept, "PLAYER_DETAILS_SET\n");
					echo "TCP: Player details updated\n";
					break;
				case "listDevices":
					$devices = $this->owner->getDevicePool()->toArray();
					$devJson = array();
					foreach($devices as $id => $device)
						try{
							$reflection = new \ReflectionClass($device);
						}catch(\ReflectionException $exception){
							continue; // F for the object
						}
					$devJson[$id] = array(
						"type"    => $reflection->getShortName(),
						"on"      => $device->isActive(),
						"current" => $device->getCurrentColor()->asHex(),
						"chosen"  => ($this->owner->getUserChosenColor($id) === null ? null :
							$this->owner->getUserChosenColor($id)->asHex()),
					);
					socket_write($accept, base64_encode(json_encode($devJson)));
					echo "TCP: Devices list sent.";
					break;
				case "setDevice":
					if(!isset($str[0]) || !isset($str[1])){
						socket_write($accept, "SETDEVICE_NOT_ENOUGH_ARGS\n");
						echo "TCP: Not enough arguments on setDevice\n";
						break;
					}
					$device = $this->owner->getDevicePool()->get($str[0], false);
					if(!isset($device)){
						socket_write($accept, "SETDEVICE_DEVICE_NOT_FOUND\n");
						echo "TCP: Device $str[0] not found\n";
						break;
					}
					$state = strtolower($str[1]);
					if($state !== "on" && $state !== "off"){
						socket_write($accept, "SETDEVICE_UNRECOGNIZED_SWITCH\n");
						echo "TCP: Unrecognized switch $state for setDevice (device $str[0])\n";
						break;
					}
					$device->setActive($state == "on" ? true : false);
					socket_write($accept, "SETDEVICE_SUCCESS\n");
					echo "TCP: Device $str[0] set to $state\n";
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
				case "update":
					$updater = new Updater();
					echo "TCP: Trying to update server...\n";
					if($updater->update()){
						socket_write($accept, "UPDATE_SUCCESS\n");
						echo "Update successful; restarting...\n";
						$this->owner->setShouldExit(2);
					}else{
						socket_write($accept, "UPDATE_NOT_NECESSARY\n");
						echo "Update not necessary\n";
					}
					break;
				default:
					socket_write($accept, "UNDEFINED_COMMAND\n");
					echo "TCP: Undefined command\n";
					break;
			}
			if(implode(" ", $str) !== "") socket_shutdown($accept, 2);
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