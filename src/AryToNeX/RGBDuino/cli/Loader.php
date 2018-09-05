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

// include exceptions
foreach(scandir(__DIR__ . "/exceptions/") as $file)
	if(pathinfo($file, PATHINFO_EXTENSION) == "php")
		require_once __DIR__ . "/exceptions/" . $file;

// include communicators
foreach(scandir(__DIR__ . "/communicators/") as $file)
	if(pathinfo($file, PATHINFO_EXTENSION) == "php")
		require_once __DIR__ . "/communicators/" . $file;

// include commands
require_once __DIR__ . "/commands/Command.php";
foreach(scandir(__DIR__ . "/commands/") as $file)
	if(pathinfo($file, PATHINFO_EXTENSION) == "php")
		require_once __DIR__ . "/commands/" . $file;

// include libs
foreach(scandir(__DIR__) as $file)
	if(pathinfo($file, PATHINFO_EXTENSION) == "php" && $file !== "CLI.php")
		require_once __DIR__ . "/" . $file;

// include main
include_once __DIR__ . "/CLI.php";