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
					$id = "USB-" . $serial;

					if($this->status->getDevicePool()->get($id) !== null) continue;

					try{
						$this->status->getDevicePool()->add(
							$id,
							new USBArduino($serial, $this->status->getConfig()->getValue("baudRate") ?? 9600)
						);
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
				$id = "BT-" . $btd["identifier"];

				if($this->status->getDevicePool()->get($id) !== null) continue;

				try{
					$this->status->getDevicePool()->add(
						$id,
						new BTArduino(
							$btd["mac"],
							$btd["rfcommPort"] ?? null,
							$this->status->getConfig()->getValue("baudRate") ?? 9600
						)
					);
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
				$id = "YEE-" . $yee["identifier"];

				if($this->status->getDevicePool()->get($id) !== null) continue;

				try{
					$this->status->getDevicePool()->add(
						$id,
						new Yeelight($yee["ip"])
					);
				}catch(MalformedIPException $e){
					echo "Exception in Yeelight device: " . $e->getMessage() . "\n";
					continue;
				}
				echo "Device " . $id . " connected.\n";
			}
		}
	}

}