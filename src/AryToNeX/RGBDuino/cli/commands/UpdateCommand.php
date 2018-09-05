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
use AryToNeX\RGBDuino\cli\ServerCommunicator;
use AryToNeX\RGBDuino\cli\exceptions\MalformedIPException;
use AryToNeX\RGBDuino\cli\Updater;

/**
 * Class UpdateCommand
 * @package AryToNeX\RGBDuino\cli\commands
 */
class UpdateCommand extends Command{

	public const ALIAS = "update";
	public const DESCRIPTION = "Updates the RGBDuino components.";
	public const USAGE = "update <all|cli|client|server> [ip] [port]";

	/**
	 * @param array|null $arguments
	 *
	 * @return bool
	 */
	public function run(?array $arguments) : bool{
		if(!isset($arguments[0])){
			echo "You must specify which things to update!\n";

			return false;
		}

		switch($arguments[0]){
			case "all":
				if(
					$this->updateServer($arguments[1], intval($arguments[2])) ||
					$this->updateClient() ||
					$this->updateCLI()
				)
					return true;

				return false;
				break;
			case "cli":
				if($this->updateCLI())
					return true;
				break;
			case "client":
				if($this->updateClient())
					return true;
				break;
			case "server":
				if($this->updateServer($arguments[1], intval($arguments[2])))
					return true;
				break;
			default:
				echo "Unrecognized parameter $arguments[0]!\n";

				return false;
				break;
		}

		return false;
	}

	/**
	 * @param null|string $ip
	 * @param int|null    $port
	 *
	 * @return bool
	 */
	protected function updateServer(?string $ip, ?int $port) : bool{
		try{
			if(isset($ip))
				$srv = ServerCommunicator::fromIP($ip, $port ?? 6969);
			else
				$srv = ServerCommunicator::fromClientConfig();
		}catch(MalformedIPException $e){
			echo "Malformed IP detected! You should consider manually specifying IP and port in the command!\n";

			return false;
		}

		$result = $srv->update();
		if($result){
			echo "Server updated!\n";

			return true;
		}
		echo "Server didn't update. Perhaps it's already on the latest version\n";

		return false;
	}

	/**
	 * @return bool
	 */
	protected function updateClient() : bool{
		$result = ClientCommunicator::update();

		if($result){
			echo "Client updated!\n";

			return true;
		}
		echo "Client didn't update. Perhaps it's not running or you don't have permission to update it.\n";

		return false;
	}

	/**
	 * @return bool
	 */
	protected function updateCLI() : bool{
		$updater = new Updater();

		if($updater->update()){
			echo "CLI tool updated!\n";

			return true;
		}
		echo "No updates found for the CLI updater.\n";

		return false;
	}

}