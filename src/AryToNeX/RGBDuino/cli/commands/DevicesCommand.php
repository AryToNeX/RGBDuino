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

/**
 * Class DevicesCommand
 * @package AryToNeX\RGBDuino\cli\commands
 */
class DevicesCommand extends Command{

	public const ALIAS = "devices";
	public const DESCRIPTION = "Returns a list of devices and its active state.";
	public const USAGE = "devices [ip] [port]";

	/**
	 * @param array|null $arguments
	 *
	 * @return bool
	 */
	public function run(?array $arguments) : bool{
		try{
			if(isset($arguments[0]))
				$srv = ServerCommunicator::fromIP($arguments[0], @intval($arguments[1]) ?? 6969);
			else
				$srv = ServerCommunicator::fromClientConfig();
		}catch(MalformedIPException $e){
			echo "Malformed IP detected! You should consider manually specifying IP and port in the command!\n";

			return false;
		}

		$devices = $srv->getDevices();
		if(!isset($devices)){
			echo "There was a problem while retrieving connected devices from the server. Please try again later.\n";

			return false;
		}

		echo "List of devices:\n";
		foreach($devices as $id => $data){
			echo "\t" . $id .
				":\n\t\tType: " . $data["type"] .
				"\n\t\tActive: " . ($data["on"] ? "Yes" : "No") .
				"\n\t\tCurrent color: " . $data["current"] .
				"\n\t\tChosen color: " . (is_null($data["chosen"]) ? "Not set" : $data["chosen"]) . "\n";
		}

		return true;
	}
}