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

use AryToNeX\RGBDuino\server\Color;

class SetColor extends TCPCommand{

	public function run() : array{
		if(!isset($this->arguments["color"])){
			echo "TCP: Custom color was not specified!\n";
			return [false, "COLOR_ERROR_NOT_ENOUGH_ARGS"];
		}
		if(
			isset($this->arguments["device"]) &&
			$this->arguments["device"] !== "global" &&
			$this->owner->getDevicePool()->get($this->arguments["device"], true) === null
		){
			echo "TCP: Custom color was not set due to device not found\n";
			echo "TCP: Unset custom color in device " . ($this->arguments["device"] ?? "global") . "\n";
			return [false, "COLOR_ERROR_DEVICE_NOT_FOUND"];
		}

		if($this->arguments["color"] === "none"){
			$this->owner->setUserChosenColor(null, $this->arguments["device"] ?? null);
			return [true];
		}

		$this->owner->setUserChosenColor(Color::fromHex($this->arguments["color"]), $this->arguments["device"] ?? null);
		echo "TCP: Set custom color to {$this->arguments["color"]} in device " . ($this->arguments["device"] ?? "global") . "\n";
		return [true];
	}

}