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

class DirectPut extends TCPCommand{

    public function run(): array{
        if(!$this->owner->getDirectMode()){
            echo "TCP: DirectPut: Direct mode is not enabled!\n";
            return [false, "DIRECTPUT_NOT_ENABLED"];
        }

        if(!isset($this->arguments["device"])){
            echo "TCP: DirectPut: Device not specified\n";
            return [false, "DIRECTPUT_DEVICE_NOT_SPECIFIED"];
        }

        if(!isset($this->arguments["color"])){
            echo "TCP: DirectPut: Color not specified\n";
            return [false, "DIRECTPUT_COLOR_NOT_SPECIFIED"];
        }

        if($this->arguments["device"] === "global"){
            foreach($this->owner->getDevicePool()->toArray() as $device)
                $device->sendColor(Color::fromHex($this->arguments["color"]));
            echo "TCP: Direct put color to {$this->arguments["color"]} globally\n";
            return [true];
        }

        $device = $this->owner->getDevicePool()->get($this->arguments["device"], false);
        if($device === null){
            echo "TCP: DirectPut: Device not found\n";
            return [false, "DIRECTPUT_ERROR_DEVICE_NOT_FOUND"];
        }

        $device->sendColor(Color::fromHex($this->arguments["color"]));
        echo "TCP: Direct put color to {$this->arguments["color"]} in device " . $this->arguments["device"] . "\n";
        return [true];
    }

}