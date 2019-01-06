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

namespace AryToNeX\RGBDuino\server\devices;

use AryToNeX\RGBDuino\server\exceptions\CannotOpenSerialConnectionException;
use AryToNeX\RGBDuino\server\exceptions\SerialPortNotFoundException;
use AryToNeX\RGBDuino\server\Utils;

/**
 * Class USBArduino
 *
 * @package AryToNeX\RGBDuino\server\devices
 */
class USBArduino extends Arduino{

	/**
	 * @param string $identifier
	 * @param string $tty
	 * @param int    $baudRate
	 *
	 * @throws SerialPortNotFoundException
	 * @throws CannotOpenSerialConnectionException
	 */
	public function __construct(string $identifier, ?string $tty = null, int $baudRate = 9600){
		parent::__construct($identifier);

		// check if specified port was passed via argument
		if(isset($tty))
			// check if port actually exists
			if(file_exists("/dev/" . $tty))
				$this->tty = "/dev/" . $tty;
			else
				throw new SerialPortNotFoundException("Defined TTY doesn't exist");
		else{
			// no port was passed via argument, default to first one
			$out = Utils::detectUSBArduino();
			if(empty($out)) throw new SerialPortNotFoundException("Couldn't find any USB serial ports!");
			$this->tty = "/dev/" . $out[0];
		}

		// setup TTY according to Arduino IDE
		exec(
			"stty -F " . $this->tty . " cs8 $baudRate -brkint -icrnl -imaxbel -opost -onlcr -isig -icanon -iexten -echo -echoe -echok -echoctl -echoke"
		);

		// open serial stream
		$this->stream = fopen($this->tty, "w+");
		if(!$this->stream) throw new CannotOpenSerialConnectionException("Can't establish serial connection");
		stream_set_blocking($this->stream, false);

		// wait for init
		while(trim(fgets($this->stream)) !== "init") continue;

		// retrieve identifier
		$this->computeIdentifier();
	}

	public function getTTY() : string{
		return $this->tty;
	}

	/**
	 * @return bool
	 */
	public function isConnected() : bool{
		return file_exists($this->tty);
	}

	public function close() : void{
		fclose($this->stream);
	}

	protected function computeIdentifier() : void{
		$this->sendData("id");
		usleep(20000); // let the stream populate
		$id = trim(fgets($this->stream));
		if(!empty($id))
			$this->identifier = $id;
	}

	protected function sendData(string $data) : void{
		fwrite($this->stream, $data . "\n");
	}

}