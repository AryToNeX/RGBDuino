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

namespace AryToNeX\RGBDuino\server\tcpcommands;

class SaveColor extends TCPCommand{

	public function run() : array{
		if(isset($this->arguments["device"]) && $this->arguments["device"] !== "global"){
			if(is_null($this->owner->getDevicePool()->get($this->arguments["device"]))){
				echo "TCP: Device {$this->arguments["device"]} not found\n";
				return [false, "DEVICE_NOT_FOUND"];
			}
			$this->owner->getDevicePool()->get($this->arguments["device"])->saveDisplayedColor();
		}else{
			foreach($this->owner->getDevicePool()->toArray() as $device)
				$device->saveDisplayedColor();
		}
		echo "TCP: Color of device " . ($str[0] ?? "global") . " saved\n";
		return [true];
	}
}