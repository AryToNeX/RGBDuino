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

namespace AryToNeX\RGBDuino\server;

use AryToNeX\RGBDuino\server\devices\Device;

/**
 * Class DevicePool
 * @package AryToNeX\RGBDuino\server
 */
class DevicePool{

	/** @var Device[] */
	private $pool = array();

	/**
	 * @param string $identifier
	 * @param Device $device
	 *
	 * @return bool
	 */
	public function add(string $identifier, Device $device) : bool{
		if(isset($this->pool[$identifier])) return false;
		$this->pool[$identifier] = $device;

		return true;
	}

	/**
	 * @param string $identifier
	 * @param bool   $caseSensitive
	 *
	 * @return bool
	 */
	public function remove(string $identifier, bool $caseSensitive = true){
		if(!$caseSensitive){
			$keys = array_keys($this->pool);
			foreach($keys as $key){
				if(strtolower($identifier) === strtolower($key)){
					$identifier = $key;
					break;
				}
			}
		}

		if(!isset($this->pool[$identifier])) return false;
		unset($this->pool[$identifier]);

		return true;
	}

	/**
	 * @param string $identifier
	 * @param bool   $caseSensitive
	 *
	 * @return Device|null
	 */
	public function get(string $identifier, bool $caseSensitive = true) : ?Device{
		if(!$caseSensitive){
			$keys = array_keys($this->pool);
			foreach($keys as $key){
				if(strtolower($identifier) === strtolower($key)){
					$identifier = $key;
					break;
				}
			}
		}

		return $this->pool[$identifier] ?? null;
	}

	/**
	 * @param int $index
	 *
	 * @return Device|null
	 */
	public function getByIndex(int $index) : ?Device{
		return array_values($this->pool)[$index] ?? null;
	}

	/**
	 * @return Device|null
	 */
	public function getFirst() : ?Device{
		return $this->getByIndex(0);
	}

	/**
	 * @param Device $device
	 *
	 * @return null|string
	 */
	public function getIdentifier(Device $device) : ?string{
		foreach($this->pool as $id => $dev){
			if($device === $dev) return $id;
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
	 * @return Device[]
	 */
	public function toArray() : array{
		return $this->pool;
	}

}