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

use AryToNeX\RGBDuino\server\Status;

abstract class TCPCommand{

	protected $owner;
	protected $ip, $port;
	protected $arguments;

	public function __construct(Status $owner, string $ip, int $port, array $arguments){
		$this->owner = $owner;
		$this->ip = $ip;
		$this->port = $port;
		$this->arguments = $arguments;
	}

    /**
     *
     * RETURN IS AN ARRAY
     * [
     *   IS THE COMMAND GONE WELL?,
     *   COMMAND DATA (OPTIONAL),
     *   SHOULD WE STOP THE FADER PREMATURELY? (OPTIONAL)
     * ]
     *
     * @return array
     */
	abstract public function run() : array;
}