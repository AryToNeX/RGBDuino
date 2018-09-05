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

/**
 * Class Updater
 * @package AryToNeX\RGBDuino\server
 */
class Updater{

	const PRODUCT = "Server";

	/** @var int */
	protected $currentVersion;
	/** @var int */
	protected $newVersion;

	/**
	 * Updater constructor.
	 */
	public function __construct(){
		$this->currentVersion = $this->loadCurrentVersion();
		$this->newVersion = intval(
			file_get_contents(
				"http://tony0000.altervista.org/RGBDuino/currentbuild.txt"
			)
		);
	}

	/**
	 * @return bool
	 */
	public function isUpdateAvailable() : bool{
		if($this->currentVersion == -1) return false; // ALWAYS USE PREBUILTS IF YOU WANT MAGIC UPDATES

		if($this->currentVersion < $this->newVersion) return true;

		return false;
	}

	/**
	 * @return bool
	 */
	public function update() : bool{
		if(!$this->isUpdateAvailable()) return false;

		copy(
			"http://tony0000.altervista.org/RGBDuino/builds/$this->newVersion/RGBDuino-" . self::PRODUCT . ".phar",
			\Phar::running(false)
		);

		$this->currentVersion = $this->newVersion;
		$this->saveCurrentVersion();

		return true;
	}

	/**
	 * @return int
	 */
	protected function loadCurrentVersion() : int{
		$file = file_get_contents(
			"/home/" . exec("whoami") . "/.local/share/RGBDuino-" . self::PRODUCT . "/current-build"
		);
		if($file === "from-source") return -1;

		return intval($file) ?? 0;
	}

	protected function saveCurrentVersion() : void{
		file_put_contents(
			"/home/" . exec("whoami") . "/.local/share/RGBDuino-" . self::PRODUCT . "/current-build",
			$this->currentVersion
		);
	}

}