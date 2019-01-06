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

class DirectMode extends TCPCommand{

    public function run(): array{
        if(!isset($this->arguments["switch"]) || !is_bool($this->arguments["switch"])){
            echo "TCP: Not enough arguments on direct mode\n";
            return [false, "DIRECTMODE_NOT_SPECIFIED"];
        }

        $this->owner->setDirectMode($this->arguments["switch"]);
        if($this->arguments["switch"] === true) foreach($this->owner->getDevicePool()->toArray() as $device){
            $device->sendColor(Color::fromHex("000000"));
        }
        return [true, null, true];
    }

}