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

class ColorCommand extends Command{

	public const ALIAS = "color";
	public const DESCRIPTION = "Save to device memory the displayed color (where applicable);\nset a new color or unset it.";
	public const USAGE = "color <hex|none|save> [global|identifier] [ip] [port]";

	public function run(?array $arguments) : bool{
		if(!isset($arguments[0])){
			echo "You must specify an hexadecimal color or 'none' to unset the custom color!\n";

			return false;
		}

		if(!isset($arguments[1])) $arguments[1] = "global";

		try{
			if(isset($arguments[2]))
				$srv = ServerCommunicator::fromIP($arguments[2], intval($arguments[3]) ?? 6969);
			else
				$srv = ServerCommunicator::fromClientConfig();
		}catch(MalformedIPException $e){
			echo "Malformed IP detected! You should consider manually specifying IP and port in the command!\n";

			return false;
		}

		if($arguments[0] == "save"){
			$result = $srv->saveColor($arguments[1]);

			if($result){
				echo "Color saved in device $arguments[1]!\n";

				return true;
			}
			echo "Couldn't save the color. Perhaps you typed an unrecognized device or the server is not online or it hanged.\n";

			return true;
		}

		if($arguments[0] == "none"){
			$result = $srv->setColor(null, $arguments[1]);

			if($result){
				echo "Color unset in device $arguments[1]!\n";

				return true;
			}
			echo "Couldn't unset the color. Perhaps you typed an unrecognized device or the server is not online or it hanged.\n";

			return true;
		}

		$result = $srv->setColor($arguments[0], $arguments[1]);

		if($result){
			echo "Color set to $arguments[0] in device $arguments[1]!\n";

			return true;
		}

		echo "Couldn't set the color. Perhaps you typed an unrecognized device or the server is offline or it hanged or your color hex is incorrect.\n";

		return false;
	}
}