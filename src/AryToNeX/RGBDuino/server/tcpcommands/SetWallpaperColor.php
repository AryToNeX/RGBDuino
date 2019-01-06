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

use AryToNeX\RGBDuino\server\Color;

class SetWallpaperColor extends TCPCommand{

	public function run() : array{
		if(!isset($this->arguments["color"])){
			echo "TCP: Wallpaper color was not specified!\n";
			return [false, "COLOR_NOT_SPECIFIED"];
		}
		if($this->arguments["color"] === "none")
			$this->owner->setWallpaperColor(null);
		else
			$this->owner->setWallpaperColor(Color::fromHex($this->arguments["color"]));
		$this->owner->setWallpaperChanged(true);
		echo "TCP: Set wallpaper color to {$this->arguments["color"]}\n";
		return [true];
	}

}