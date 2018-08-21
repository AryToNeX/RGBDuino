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

use AryToNeX\RGBDuino\exceptions\CannotOpenSerialConnectionException;
use AryToNeX\RGBDuino\exceptions\NoArduinoConnectedException;
use AryToNeX\RGBDuino\exceptions\TTYNotFoundException;

class Arduino{

    private $sock;
    private $tty;

    /**
     * @throws NoArduinoConnectedException
     * @throws TTYNotFoundException
     * @throws CannotOpenSerialConnectionException
     *
     * @param $tty string
     */
    public function __construct(?string $tty = null){
        exec("ls /dev/ | grep ttyUSB", $out);
        if(empty($out)) throw new NoArduinoConnectedException("No Arduino devices found");

        if(!isset($tty))
            $this->tty = "/dev/".$out[0];
        else
            if(in_array($tty, $out))
                $this->tty = "/dev/".$tty;
            else
            	throw new TTYNotFoundException("Defined TTY doesn't exist");

        exec("stty -F ".$this->tty." cs8 9600 ignbrk -brkint -imaxbel -opost -onlcr -isig -icanon -iexten -echo -echoe -echok -echoctl -echoke noflsh -ixon -crtscts");

        $this->sock = fopen($this->tty, "w+");
        if(!$this->sock) throw new CannotOpenSerialConnectionException("Can't establish serial connection");

        sleep(1);
    }

    public function sendColorArray(array $rgb) : void{
    	$this->sendColor($rgb["r"], $rgb["g"], $rgb["b"]);
    }
    
    public function sendColor($r, $g, $b) : void{
    	$r = ($r < 0 ? 0 : ($r > 255 ? 255 : $r));
		$g = ($g < 0 ? 0 : ($g > 255 ? 255 : $g));
		$b = ($b < 0 ? 0 : ($b > 255 ? 255 : $b));

    	$color =
			"r" . str_pad(intval($r), 3, '0', STR_PAD_LEFT) .
			"g" . str_pad(intval($g), 3, '0', STR_PAD_LEFT) .
			"b" . str_pad(intval($b), 3, '0', STR_PAD_LEFT);

        // WRITE
        fwrite($this->sock, $color . "\n");
	}

    public function saveDisplayedColor() : void{
    	fwrite($this->sock, "save\n");
	}

    public function close() : void{
        fclose($this->sock);
    }

}