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
 * Class Utils
 *
 * @package AryToNeX\RGBDuino\server
 */
class Utils{

	// Device detection utils

	/**
	 * @return array
	 */
	public static function detectUSBArduino(){
		// list USB serial ports
		return array_values(
			array_filter(
				scandir("/dev/"),
				function($val){
					return (strpos($val, "ttyUSB") !== false);
				}
			)
		);
	}

	/**
	 * @return array
	 */
	public static function detectBluetoothArduino(){
		// list Bluetooth serial ports
		return array_values(
			array_filter(
				scandir("/dev/"),
				function($val){
					return (strpos($val, "rfcomm") !== false);
				}
			)
		);
	}

    /**
     * @param        $sock
     * @param string $str
     * @param bool   $or_until_data_finish
     * @param int    $timeout
     *
     * @return string
     */
    public static function socket_read_until($sock,
                                                string $str,
                                                bool $or_until_data_finish = true,
                                                int $timeout = 5) : string{
        $data = "";
        $buf = "";
        $preTime = time();
        while(true){
            $by = socket_recv($sock, $buf, 1, MSG_DONTWAIT);

            // if char reached break
            if($buf === $str) break;

            // if remote disconnects break
            if($by === 0) break;

            // if connection timeouts break
            if(time() - $preTime > $timeout) break;

            // if data finishes break
            if($or_until_data_finish && $buf === null){
                if($data !== "") break;
            }

            // add buffer to data string
            $data .= $buf;
        }

        return $data;
    }

}