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

use AryToNeX\RGBDuino\exceptions\NoAlbumArtException;

class PlayerCtl{
    
    private $binary;
    private $player;
    
    public function __construct(string $player = null, string $PATH = "/usr/bin"){
        $this->binary = $PATH . "/playerctl";
        if(isset($player)) $this->player = $player;
        else $this->player = $this->getPlayers()[0] ?? null;
    }

    /** @throws \Exception */
    public function getPosition() : ?int{
        if(is_null($this->player)) throw new \Exception("No music player was set!");
        return intval(exec($this->binary . " -p " . $this->player . " position 2>/dev/null")) ?? null;
    }

    /** @throws \Exception */
    public function getTotalDuration() : ?int{
        if(is_null($this->player)) throw new \Exception("No music player was set!");
        return
            (intval(exec($this->binary . " -p " . $this->player . " metadata mpris:length 2>/dev/null")) / 100000)
            ?? null;
    }

    /** @throws \Exception */
    public function getStatus() : ?string{
        if(is_null($this->player)) throw new \Exception("No music player was set!");
        return trim(strval(exec($this->binary . " -p " . $this->player . " status 2>/dev/null"))) ?? null;
    }

    /** @throws \Exception */
    public function getArtist() : ?string{
        if(is_null($this->player)) throw new \Exception("No music player was set!");
        return strval(exec($this->binary . " -p " . $this->player . " metadata artist 2>/dev/null")) ?? null;
    }

    /** @throws \Exception */
    public function getTitle() : ?string{
        if(is_null($this->player)) throw new \Exception("No music player was set!");
        return strval(exec($this->binary . " -p " . $this->player . " metadata title 2>/dev/null")) ?? null;
    }

    /**
     * @throws \Exception
     * @throws NoAlbumArtException
     */
    public function getAlbumArtURL() : ?string{
        if(is_null($this->player)) throw new \Exception("No music player was set!");
        $ret = urldecode(strval(exec($this->binary . " -p " . $this->player . " metadata mpris:artUrl 2>/dev/null"))) ?? null;
        if(is_null($ret) || $ret == "") throw new NoAlbumArtException("Album art not defined or NULL on MPRIS2.");
        return $ret;
    }

    public function getPlayers() : array{
        exec($this->binary . " -l 2>/dev/null", $output);
        return $output;
    }

    public function getActivePlayer() : ?string{
        return $this->player;
    }

    public function setActivePlayer(?string $player) : void{
        $this->player = $player;
    }
}