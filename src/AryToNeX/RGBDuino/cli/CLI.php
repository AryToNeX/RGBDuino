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

namespace AryToNeX\RGBDuino\cli;

// create the command factory
$factory = new CommandFactory();

// remove the filename argument
array_shift($argv);

if(empty($argv) || $argv[0] === "help"){ // we exclude "help" command as well
	$factory->getCommand("help")->run(null);
	exit(0);
}

// take the command
$cmd = array_shift($argv);

// strtolower on all arguments
$argv = array_filter($argv, 'strtolower');

// pass it to the factory w/ error checking
if(!$factory->getCommand($cmd)->run($argv)){
	echo "Usage: " . $factory->getUsage($cmd) . "\n";
	exit(-1);
}