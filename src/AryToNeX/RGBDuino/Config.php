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

/**
 * Class Config
 *
 * @package AryToNeX\RGBDuino
 */
class Config{

	/** @var string */
	private $path;
	/** @var array */
	private $data;

	/**
	 * Config constructor.
	 *
	 * @param null|string $cfgpath
	 */
	public function __construct(?string $cfgpath = null){
		if(!isset($cfgpath)) $this->path = "/home/" . exec("whoami") . "/.local/share/RGBDuino/config.json";
		else $this->path = $cfgpath;
		$this->data = json_decode(file_get_contents($this->path), true);
		$this->fixMissing();
		$this->update();
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getValue(string $key){
		return $this->data[$key];
	}

	protected function fixMissing() : void{
		if(!is_file($this->path)){
			@mkdir("/home/" . exec("whoami") . "/.local/share/RGBDuino/", 0755, true);
			copy(__DIR__ . "/resources/config.json", $this->path);
		}
	}

	protected function update() : void{
		$updated = false;
		$current = json_decode(file_get_contents(__DIR__ . "/resources/config.json"), true);
		foreach($current as $key => $value){
			if(isset($this->data[$key])) $current[$key] = $this->data[$key];
			else $updated = true;
		}
		if($updated){
			$this->data = $current;
			file_put_contents($this->path, json_encode($this->data, JSON_PRETTY_PRINT));
		}
	}

}