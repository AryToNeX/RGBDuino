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

/**
 * Class FakeArduino
 * @package AryToNeX\RGBDuino\arduino
 */
class FakeArduino extends Arduino{

	/**
	 * FakeArduino constructor.
	 */
	public function __construct(){
		echo "Fake arduino opened!\n";
	}

	public function close() : void{
		echo "Fake Arduino connection closed!\n";
	}

	protected function sendData(string $data) : void{
		echo "Fake Arduino received: $data\n";
	}

}