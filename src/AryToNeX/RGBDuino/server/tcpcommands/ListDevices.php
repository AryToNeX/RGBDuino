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

class ListDevices extends TCPCommand{

    public function run() : array{
        $devices = $this->owner->getDevicePool()->toArray();
        $devJson = array();
        foreach($devices as $id => $device){
            try{
                $reflection = new \ReflectionClass($device);
            }catch(\ReflectionException $exception){
                continue; // F for the object
            }
            $devJson[$id] = array(
                "type"    => $reflection->getShortName(),
                "on"      => $device->isActive(),
                "current" => $device->getCurrentColor()->asHex(),
                "chosen"  => ($this->owner->getUserChosenColor($id) === null ? null :
                    $this->owner->getUserChosenColor($id)->asHex()),
            );
        }
        echo "TCP: Devices list sent.\n";
        return [true, $devJson];
    }

}