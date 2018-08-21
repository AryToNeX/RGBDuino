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

namespace AryToNeX\RGBDuino;

use AryToNeX\RGBDuino\arduino\Arduino;

/**
 * Class ArduinoPool
 * @package AryToNeX\RGBDuino
 */
class ArduinoPool{

	/** @var Arduino[] */
	private $pool = array();

	/**
	 * @param string  $identifier
	 * @param Arduino $arduino
	 *
	 * @return bool
	 */
	public function add(string $identifier, Arduino $arduino) : bool{
		if(isset($this->pool[$identifier])) return false;
		$this->pool[$identifier] = $arduino;

		return true;
	}

	/**
	 * @param string $identifier
	 *
	 * @return bool
	 */
	public function remove(string $identifier){
		if(!isset($this->pool[$identifier])) return false;
		unset($this->pool[$identifier]);

		return true;
	}

	/**
	 * @param string $identifier
	 *
	 * @return Arduino|null
	 */
	public function get(string $identifier) : ?Arduino{
		return $this->pool[$identifier] ?? null;
	}

	/**
	 * @param int $index
	 *
	 * @return Arduino|null
	 */
	public function getByIndex(int $index) : ?Arduino{
		return array_values($this->pool)[$index] ?? null;
	}

	/**
	 * @return Arduino|null
	 */
	public function getFirst() : ?Arduino{
		return $this->getByIndex(0);
	}

	/**
	 * @param Arduino $arduino
	 *
	 * @return null|string
	 */
	public function getIdentifier(Arduino $arduino) : ?string{
		foreach($this->pool as $id => $ard){
			if($arduino === $ard) return $id;
		}

		return null;
	}

	/**
	 * @return int
	 */
	public function count() : int{
		return count($this->pool);
	}

	/**
	 * @return Arduino[]
	 */
	public function toArray() : array{
		return $this->pool;
	}

}