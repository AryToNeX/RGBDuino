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

use AryToNeX\RGBDuino\server\devices\USBArduino;
use AryToNeX\RGBDuino\server\devices\BTArduino;
use AryToNeX\RGBDuino\server\devices\Yeelight;
use AryToNeX\RGBDuino\server\exceptions\CannotOpenSerialConnectionException;
use AryToNeX\RGBDuino\server\exceptions\MalformedIPException;
use AryToNeX\RGBDuino\server\exceptions\MalformedMACAddressException;
use AryToNeX\RGBDuino\server\exceptions\RFCOMMPortExistsException;
use AryToNeX\RGBDuino\server\exceptions\SerialPortNotFoundException;

/**
 * Class DeviceDiscovery
 * @package AryToNeX\RGBDuino\server
 */
class DeviceDiscovery{

	/** @var Status */
	protected $status;
	/** @var int */
	protected $lastTime;

	/**
	 * DeviceDiscovery constructor.
	 *
	 * @param Status $status
	 */
	public function __construct(Status $status){
		$this->status = $status;
	}

	public function checkDisconnected() : void{
		foreach($this->status->getDevicePool()->toArray() as $id => $device){
			if(!$device->isConnected()){
				echo "Device $id disconnected.\n";
				$this->status->getDevicePool()->remove($id);
			}
		}
	}

	/**
	 * @return bool
	 */
	public function isTimeToCheck() : bool{
		$time = $this->status->getConfig()->getValue("checkEvery") ?? 30;
		if($time < 1) $time = 1;
		if($time > 60) $time = 60;

		return (time() - $this->lastTime > $time);
	}

	public function checkConnected() : void{
		$this->checkUSBConnected();
		$this->checkBluetoothConnected();
		$this->checkYeelightConnected();
		$this->lastTime = time();
	}

	public function checkUSBConnected() : void{
		if($this->status->getConfig()->getValue("useUsb") ?? true){
			$serials = Utils::detectUSBArduino();
			if(!empty($serials))
				foreach($serials as $serial){
					foreach($this->status->getDevicePool()->toArray() as $device){
						if($device instanceof USBArduino){
							if($device->getTTY() === "/dev/" . $serial) continue 2;
						}
					}

					try{
						$device = new USBArduino("USB-".$serial, $serial, $this->status->getConfig()->getValue
						("baudRate") ??
							9600);
						$id = $device->getIdentifier();
						$this->status->getDevicePool()->add($device);
					}catch(SerialPortNotFoundException | CannotOpenSerialConnectionException $e){
						echo "Exception in USB device: " . $e->getMessage() . "\n";
						continue;
					}
					echo "Device " . $id . " connected.\n";
				}
		}
	}

	public function checkBluetoothConnected() : void{
		if($this->status->getConfig()->getValue("useBluetooth") ?? false){
			foreach($this->status->getConfig()->getValue("bluetooth") as $btd){

				foreach($this->status->getDevicePool()->toArray() as $device){
					if($device instanceof BTArduino){
						if($device->getMAC() === $btd["mac"]) continue 2;
					}
				}

				try{
					$device = new BTArduino(
						$btd["identifier"],
						$btd["mac"],
						$btd["rfcommPort"] ?? null,
						$this->status->getConfig()->getValue("baudRate") ?? 9600
					);
					$id = $device->getIdentifier();
					$this->status->getDevicePool()->add($device);
				}catch(
				CannotOpenSerialConnectionException |
				MalformedMACAddressException |
				SerialPortNotFoundException |
				RFCOMMPortExistsException $e
				){
					echo "Exception in Bluetooth device: " . $e->getMessage() . "\n";
					continue;
				}
				echo "Device " . $id . " connected.\n";
			}
		}
	}

	public function checkYeelightConnected() : void{
		if($this->status->getConfig()->getValue("useYeelight") ?? false){
			foreach($this->status->getConfig()->getValue("yeelight") as $yee){

				foreach($this->status->getDevicePool()->toArray() as $device){
					if($device instanceof Yeelight){
						if($device->getIp() === $yee["ip"]) continue 2;
					}
				}

				try{
					$device = new Yeelight($yee["identifier"], $yee["ip"]);
					$id = $device->getIdentifier();
					$this->status->getDevicePool()->add($device);
				}catch(MalformedIPException $e){
					echo "Exception in Yeelight device: " . $e->getMessage() . "\n";
					continue;
				}
				echo "Device " . $id . " connected.\n";
			}
		}
	}

}