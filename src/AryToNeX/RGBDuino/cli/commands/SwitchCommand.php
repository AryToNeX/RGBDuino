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

namespace AryToNeX\RGBDuino\cli\commands;

use AryToNeX\RGBDuino\cli\exceptions\MalformedIPException;
use AryToNeX\RGBDuino\cli\ServerCommunicator;

class SwitchCommand extends Command{

	public const ALIAS = "switch";
	public const DESCRIPTION = "Turns a controlled LED strip on or off.";
	public const USAGE = "switch <identifier> <on|off> [ip] [port]";

	public function run(?array $arguments) : bool{
		if(!isset($arguments[0])){
			echo "You must specify a device identifier!";

			return false;
		}

		if(!isset($arguments[1])){
			echo "You must specify a mode to set your device on!";

			return false;
		}

		if(strtolower($arguments[1]) !== "on" && strtolower($arguments[1]) !== "off"){
			echo "The mode must be 'on' or 'off'. You specified '$arguments[1]'\n";

			return false;
		}

		try{
			if(isset($arguments[2]))
				$srv = ServerCommunicator::fromIP($arguments[2], intval($arguments[3]) ?? 6969);
			else
				$srv = ServerCommunicator::fromClientConfig();
		}catch(MalformedIPException $e){
			echo "Malformed IP detected! You should consider manually specifying IP and port in the command!\n";

			return false;
		}

		$result = $srv->setDevice($arguments[0], (strtolower($arguments[1]) === "on" ? true : false));

		if($result){
			echo "Device $arguments[0] successfully set to $arguments[1]!\n";

			return true;
		}
		echo "Device $arguments[0] not found.\n";

		return false;
	}

}