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

// include arduino types
foreach(scandir(__DIR__ . "/AryToNeX/RGBDuino/arduino/") as $file)
	if(pathinfo($file, PATHINFO_EXTENSION) == "php")
		require_once __DIR__ . "/AryToNeX/RGBDuino/arduino/" . $file;

// include color palette utils (color-extractor modified to work on php7)
foreach(scandir(__DIR__ . "/AryToNeX/RGBDuino/color/") as $file)
	if(pathinfo($file, PATHINFO_EXTENSION) == "php")
		require_once __DIR__ . "/AryToNeX/RGBDuino/color/" . $file;

// include exceptions
foreach(scandir(__DIR__ . "/AryToNeX/RGBDuino/exceptions/") as $file)
	if(pathinfo($file, PATHINFO_EXTENSION) == "php")
		require_once __DIR__ . "/AryToNeX/RGBDuino/exceptions/" . $file;

// include libs
foreach(scandir(__DIR__ . "/AryToNeX/RGBDuino/") as $file)
	if(pathinfo($file, PATHINFO_EXTENSION) == "php" && $file !== "RGBDuino.php")
		require_once __DIR__ . "/AryToNeX/RGBDuino/" . $file;

// include main
include_once __DIR__ . "/AryToNeX/RGBDuino/RGBDuino.php";