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

ini_set("phar.readonly", 0);

@mkdir("build/");

// BUILD SERVER
if(is_file("build/RGBDuino-Server.phar")) unlink("build/RGBDuino-Server.phar");
$phar = new Phar("build/RGBDuino-Server.phar", null, "RGBDuino-Server.phar");
$phar->buildFromIterator(
	new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			"src/AryToNeX/RGBDuino/server",
			RecursiveDirectoryIterator::SKIP_DOTS
		)
	),
	"src/"
);
$phar->setStub($phar->createDefaultStub("AryToNeX/RGBDuino/server/Loader.php"));

// BUILD CLIENT
if(is_file("build/RGBDuino-Client.phar")) unlink("build/RGBDuino-Client.phar");
$phar = new Phar("build/RGBDuino-Client.phar", null, "RGBDuino-Client.phar");
$phar->buildFromIterator(
	new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			"src/AryToNeX/RGBDuino/client",
			RecursiveDirectoryIterator::SKIP_DOTS
		)
	),
	"src/"
);
$phar->setStub($phar->createDefaultStub("AryToNeX/RGBDuino/client/Loader.php"));

// BUILD CLI
if(is_file("build/RGBDuino-CLI.phar")) unlink("build/RGBDuino-CLI.phar");
$phar = new Phar("build/RGBDuino-CLI.phar", null, "RGBDuino-CLI.phar");
$phar->buildFromIterator(
	new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			"src/AryToNeX/RGBDuino/cli",
			RecursiveDirectoryIterator::SKIP_DOTS
		)
	),
	"src/"
);
$phar->setStub($phar->createDefaultStub("AryToNeX/RGBDuino/cli/Loader.php"));

file_put_contents("build/current-build", "from-source");
file_put_contents(
	"build/rgbcli",
	"#!/bin/bash

php /home/$(whoami)/.local/share/RGBDuino-CLI/RGBDuino-CLI.phar \"$@\"
"
);
chmod("build/rgbcli", 0755);

echo "PHAR archives successfully created.\n";