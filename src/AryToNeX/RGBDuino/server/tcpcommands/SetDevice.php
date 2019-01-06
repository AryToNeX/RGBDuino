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

class SetDevice extends TCPCommand{

    public function run() : array{

        if(!isset($this->arguments["device"])){
            echo "TCP: No device specified on setDevice\n";
            return [false, "SETDEVICE_NO_DEVICE_SPECIFIED"];
        }

        if(!isset($this->arguments["switch"]) || !is_bool($this->arguments["switch"])){
            echo "TCP: No device switch specified on setDevice\n";
            return [false, "SETDEVICE_NO_SWITCH_SPECIFIED"];
        }

        if(strtolower($this->arguments["device"]) === "global"){
            foreach($this->owner->getDevicePool()->toArray() as $device)
                $device->setActive($this->arguments["switch"]);

            echo "TCP: Global context switched to {$this->arguments["switch"]}\n";
            return [true, null, true];
        }

        $device = $this->owner->getDevicePool()->get($this->arguments["device"], false);
        if(!isset($device)){
            echo "TCP: SetDevice: Device {$this->arguments["device"]} not found\n";
            return [false, "SETDEVICE_DEVICE_NOT_FOUND"];
        }

        $device->setActive($this->arguments["switch"]);
        echo "TCP: Device {$this->arguments["device"]} switched to {$this->arguments["switch"]}\n";
        return [true, null, true];
    }

}