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
use AryToNeX\RGBDuino\server\exceptions\MalformedMACAddressException;
use AryToNeX\RGBDuino\server\exceptions\RFCOMMPortExistsException;
use AryToNeX\RGBDuino\server\exceptions\SerialPortNotFoundException;
use AryToNeX\RGBDuino\server\Utils;

/**
 * Class BTArduino
 * @package AryToNeX\RGBDuino\server\devices
 */
class BTArduino extends Arduino{

	/** @var string */
	private $mac;

	/**
	 * BTArduino constructor.
	 *
	 * @param string $macAddress
	 * @param int    $rfcommPort
	 * @param int    $baudRate
	 *
	 * @throws CannotOpenSerialConnectionException
	 * @throws MalformedMACAddressException
	 * @throws SerialPortNotFoundException
	 * @throws RFCOMMPortExistsException
	 */
	public function __construct(string $macAddress, ?int $rfcommPort = null, int $baudRate = 9600){
		parent::__construct();

		// MAC ADDRESS SANITY CHECK
		// it MUST be uppercase and formatted as XX:XX:XX:YY:YY:YY
		$macAddress = strtoupper($macAddress);
		$macAddress = filter_var($macAddress, FILTER_VALIDATE_MAC);
		if($macAddress === false) throw new MalformedMACAddressException("MAC address is not correct!");

		// Save MAC address to object
		$this->mac = $macAddress;

		// check if specified port was passed via argument
		if(isset($rfcommPort))
			// check if port already is bound to something
			if(file_exists("/dev/rfcomm") . $rfcommPort)
				throw new RFCOMMPortExistsException("Defined RFCOMM port is already bound to something");
			else{
				// no port was passed via argument, default to the first free one
				$out = Utils::detectBluetoothArduino();
				$rfcommPort = intval(
						substr($out[count($out) - 1], -1)
					) + 1;
			}

		// Bind to /dev/rfcommN via rfcomm command
		exec("rfcomm bind $rfcommPort $this->mac 1", $out, $status);

		// check if serial port opened correctly
		if(!file_exists("/dev/rfcomm") . $rfcommPort)
			throw new SerialPortNotFoundException("Unable to find RFCOMM serial port after binding");

		$this->tty = "/dev/rfcomm" . $rfcommPort;

		// setup TTY according to Arduino IDE
		exec(
			"stty -F " . $this->tty . " cs8 $baudRate -brkint -icrnl -imaxbel -opost -onlcr -isig -icanon -iexten -echo -echoe -echok -echoctl -echoke"
		);

		// open serial stream
		$this->stream = fopen($this->tty, "w+");
		if(!$this->stream) throw new CannotOpenSerialConnectionException("Can't establish serial connection");
	}

	/**
	 * @return bool
	 */
	public function isConnected() : bool{
		return file_exists($this->tty);
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