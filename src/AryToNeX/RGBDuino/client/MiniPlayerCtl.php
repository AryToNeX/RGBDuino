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

namespace AryToNeX\RGBDuino\client;

use AryToNeX\RGBDuino\client\exceptions\NoAlbumArtException;

/**
 * Class MiniPlayerCtl
 *
 * @package AryToNeX\RGBDuino\client
 */
class MiniPlayerCtl{

	/** @var string */
	private $binary;
	/** @var mixed|null */
	private $player;

	/**
	 * PlayerCtl constructor.
	 *
	 * @param string|null $player
	 * @param string      $PATH
	 */
	public function __construct(string $player = null, string $PATH = "/usr/bin"){
		$this->binary = $PATH . "/playerctl";
		if(isset($player)) $this->player = $player;
		else $this->player = $this->getPlayers()[0] ?? null;
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function isPlaying() : bool{
		if(is_null($this->player)) throw new \Exception("No music player was set!");

		$status = trim(strval(exec($this->binary . " -p " . $this->player . " status 2>/dev/null"))) ?? null;
		if(isset($status) && $status === "Playing") return true;

		return false;
	}

	/**
	 * @throws \Exception
	 * @throws NoAlbumArtException
	 *
	 * @return null|string
	 */
	public function getAlbumArtURL() : ?string{
		if(is_null($this->player)) throw new \Exception("No music player was set!");

		$ret = urldecode(
				strval(exec($this->binary . " -p " . $this->player . " metadata mpris:artUrl 2>/dev/null"))
			) ?? null;

		$ret = str_replace(
			"https://open.spotify.com/",
			"http://i.scdn.co/",
			$ret
		);

		if(is_null($ret) || $ret == "")
			throw new NoAlbumArtException("Album art not defined or NULL.");

		return $ret;
	}

	/**
	 * @return array
	 */
	public function getPlayers() : array{
		exec($this->binary . " -l 2>/dev/null", $output);

		return $output;
	}

	/**
	 * @return null|string
	 */
	public function getActivePlayer() : ?string{
		return $this->player;
	}

	/**
	 * @param null|string $player
	 */
	public function setActivePlayer(?string $player) : void{
		$this->player = $player;
	}
}