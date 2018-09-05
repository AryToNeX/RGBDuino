<?php

namespace AryToNeX\RGBDuino\cli;

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

/**
 * Class ClientCommunicator
 * @package AryToNeX\RGBDuino\cli
 */
class ClientCommunicator{

	/**
	 * @return bool
	 */
	public static function restartClient() : bool{
		$pid = self::getClientPid();
		if(!isset($pid)) return false;

		return posix_kill($pid, SIGUSR1);
	}

	/**
	 * @return bool
	 */
	public static function stopClient() : bool{
		$pid = self::getClientPid();
		if(!isset($pid)) return false;

		return posix_kill($pid, SIGINT);
	}

	/**
	 * @return bool
	 */
	public static function update() : bool{
		$pid = self::getClientPid();
		if(!isset($pid)) return false;

		return posix_kill($pid, SIGUSR2);
	}

	/**
	 * @return int|null
	 */
	protected static function getClientPid() : ?int{
		$pids = self::getPids("rgbduino-client");
		if(empty($pids)) return null;

		return $pids[0];
	}

	/**
	 * @param string $process
	 *
	 * @return array
	 */
	protected static function getPids(string $process){
		return array_map('intval', explode(" ", exec("pidof $process")));
	}

}