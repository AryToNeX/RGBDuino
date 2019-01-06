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

class SetPlayerDetails extends TCPCommand{

	public function run() : array{
		if($this->owner->getPlayerStatus() === null){
			echo "TCP: Attempt to set player details discarded; not enabled in config\n";
			return [false, "PLAYER_DETAILS_NOT_ENABLED"];
		}
		
		if(!isset($this->arguments["playing"])){
			echo "TCP: Player details were not set; message is invalid\n";
			return [false, "PLAYER_DETAILS_PLAYING_NOT_SET"];
		}
		$this->owner->getPlayerStatus()->setPlaying($this->arguments["playing"]);

		if($this->arguments["playing"] && !isset($this->arguments["url"])){
			echo "TCP: Player details were not set; message is invalid\n";
			return [false, "PLAYER_DETAILS_MESSAGE_INVALID"];
		}
		$this->owner->getPlayerStatus()->setArtURL($this->arguments["url"]);


		if(isset($this->arguments["colors"]) && !empty($this->arguments["colors"])){
			$colors = array();

			foreach($this->arguments["colors"] as $rgb){
				$colors[] = Color::fromArray($rgb);
			}

			$this->owner->getPlayerStatus()->setAlbumArtColorArray($colors);
		}else $this->owner->getPlayerStatus()->setAlbumArtColorArray(null);
		return [true];
	}

}