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

use AryToNeX\RGBDuino\cli\ClientCommunicator;
use AryToNeX\RGBDuino\cli\exceptions\MalformedIPException;
use AryToNeX\RGBDuino\cli\ServerCommunicator;

class StopCommand extends Command{

	public const ALIAS = "stop";
	public const DESCRIPTION = "Stops server or client daemons.";
	public const USAGE = "stop <server|client> [ip] [port]";

	public function run(?array $arguments) : bool{
		if(!isset($arguments[0])){
			echo "You must specify whether you want the client or the server to be stopped!\n";

			return false;
		}

		switch($arguments[0]){
			case "server":
				try{
					if(isset($arguments[1]))
						$srv = ServerCommunicator::fromIP($arguments[1], intval($arguments[2]) ?? 6969);
					else
						$srv = ServerCommunicator::fromClientConfig();
				}catch(MalformedIPException $e){
					echo "Malformed IP detected! You should consider manually specifying IP and port in the command!\n";

					return false;
				}

				$result = $srv->stopServer();
				if($result)
					echo "Server stopped!\n";
				else
					echo "There was an issue while stopping the server. Perhaps it hanged or it's already stopped.\n";
				break;
			case "client":
				$result = ClientCommunicator::stopClient();
				if($result)
					echo "Client stopped!\n";
				else
					echo "There was an issue while stopping the client. Perhaps it was not running or you don't have permission to stop it.\n";
				break;
			default:
				echo "Unrecognized argument $arguments[0]!";

				return false;
		}

		return true;
	}

}