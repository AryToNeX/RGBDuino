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
use AryToNeX\RGBDuino\exceptions\MalformedMACAddressException;
use AryToNeX\RGBDuino\exceptions\TTYNotFoundException;

/**
 * Class BTArduino
 * @package AryToNeX\RGBDuino\arduino
 */
class BTArduino extends Arduino{

	/** @var string */
	private $mac;

	/**
	 * BTArduino constructor.
	 *
	 * @param string $macAddress
	 * @param int    $rfcommPort
	 *
	 * @throws CannotOpenSerialConnectionException
	 * @throws MalformedMACAddressException
	 * @throws TTYNotFoundException
	 */
	public function __construct(string $macAddress, int $rfcommPort = 0){
		// MAC ADDRESS SANITY CHECK
		// it MUST be uppercase and formatted as XX:XX:XX:YY:YY:YY
		$macAddress = strtoupper($macAddress);
		if(strlen($macAddress) !== 17) $bad = true;
		if(preg_match("/^[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}$/g", $macAddress)
			!== 1) $bad = true;
		if($bad) throw new MalformedMACAddressException("MAC address is not correct!");

		// Save MAC address to object
		$this->mac = $macAddress;

		// Bind to /dev/rfcommN via rfcomm command
		exec("rfcomm bind $rfcommPort $macAddress 1", $out, $status);
		$this->tty = "/dev/rfcomm" . $rfcommPort;

		// double check for rfcomm serial ports
		exec("ls /dev/ | grep rfcomm" . $rfcommPort, $out);
		if(empty($out)) throw new TTYNotFoundException("Unable to find RFCOMM serial port");

		// setup TTY
		exec(
			"stty -F " . $this->tty . " cs8 9600 ignbrk -brkint -imaxbel -opost -onlcr -isig -icanon -iexten -echo -echoe -echok -echoctl -echoke noflsh -ixon -crtscts"
		);

		// open serial stream
		$this->stream = fopen($this->tty, "w+");
		if(!$this->stream) throw new CannotOpenSerialConnectionException("Can't establish serial connection");
	}

	/**
	 * @param string $data
	 */
	protected function sendData(string $data) : void{
		fwrite($this->stream, $data . "\n");
	}

	public function close() : void{
		fclose($this->stream);
		exec("rfcomm release " . $this->mac);
	}
}