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

namespace AryToNeX\RGBDuino\arduino;

use AryToNeX\RGBDuino\exceptions\CannotOpenSerialConnectionException;
use AryToNeX\RGBDuino\exceptions\NoArduinoConnectedException;
use AryToNeX\RGBDuino\exceptions\SerialPortNotFoundException;
use AryToNeX\RGBDuino\Utils;

/**
 * Class USBArduino
 *
 * @package AryToNeX\RGBDuino\arduino
 */
class USBArduino extends Arduino{

	/**
	 * @throws NoArduinoConnectedException
	 * @throws SerialPortNotFoundException
	 * @throws CannotOpenSerialConnectionException
	 *
	 * @param $tty string
	 */
	public function __construct(?string $tty = null){
		// list USB serial ports
		$out = Utils::detectUSBArduino();
		if(empty($out)) throw new NoArduinoConnectedException("No Arduino devices found");

		// check if specified port was passed via argument
		if(isset($tty))
			// check if port actually exists
			if(in_array($tty, $out))
				$this->tty = "/dev/" . $tty;
			else
				throw new SerialPortNotFoundException("Defined TTY doesn't exist");
		else
			// no port was passed via argument, default to first one
			$this->tty = "/dev/" . $out[0];

		// setup TTY
		exec(
			"stty -F " . $this->tty . " cs8 9600 ignbrk -brkint -imaxbel -opost -onlcr -isig -icanon -iexten -echo -echoe -echok -echoctl -echoke noflsh -ixon -crtscts"
		);

		// open serial stream
		$this->stream = fopen($this->tty, "w+");
		if(!$this->stream) throw new CannotOpenSerialConnectionException("Can't establish serial connection");
	}

	public function close() : void{
		fclose($this->stream);
	}

	protected function sendData(string $data) : void{
		fwrite($this->stream, $data . "\n");
	}

}