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

namespace AryToNeX\RGBDuino\cli\commands;

/**
 * Class HelpCommand
 * @package AryToNeX\RGBDuino\cli\commands
 */
class HelpCommand extends Command{

	public const ALIAS = "help";
	public const DESCRIPTION = "Shows the help page.";
	public const USAGE = "help";

	/**
	 * @param array|null $arguments
	 *
	 * @return bool
	 */
	public function run(?array $arguments) : bool{
		$m = "RGBDuino-CLI vINDEV\n";
		$m .= "Usage: rgbcli <command> [arguments]\n";
		$m .= "Commands:\n";
		foreach($this->commandFactory->toArrayKV() as $cmd => $data)
			$m .= "\t" . $cmd . ":\n\t\t" . $data["description"] . "\n\t\tUsage: " . $data["usage"] . "\n";
		echo $m;

		return true;
	}

}