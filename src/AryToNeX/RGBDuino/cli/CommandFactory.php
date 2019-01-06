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

use AryToNeX\RGBDuino\cli\commands\Command;

/**
 * Class CommandFactory
 * @package AryToNeX\RGBDuino\cli
 */
class CommandFactory{

	/** @var array */
	private $commands;

	/**
	 * CommandFactory constructor.
	 */
	public function __construct(){
		foreach(scandir(__DIR__ . "/commands/") as $commandClass){
			$className = pathinfo($commandClass, PATHINFO_FILENAME);
			try{
				$reflectionClass = new \ReflectionClass("AryToNeX\RGBDuino\cli\commands\\" . $className);
			}catch(\ReflectionException $e){
				continue;
			}
			if($reflectionClass->isAbstract()) continue;
			$this->commands[] = array(
				"className"   => "AryToNeX\RGBDuino\cli\commands\\" . $className,
				"alias"       => $reflectionClass->getConstant("ALIAS"),
				"description" => $reflectionClass->getConstant("DESCRIPTION"),
				"usage"       => $reflectionClass->getConstant("USAGE"),
			);
		}
	}

	/**
	 * @param string $commandName
	 *
	 * @return null|string
	 */
	public function getDescription(string $commandName) : ?string{
		foreach($this->commands as $command)
			if($command["alias"] == $commandName)
				return $command["description"];

		return null;
	}

	/**
	 * @param string $commandName
	 *
	 * @return null|string
	 */
	public function getUsage(string $commandName) : ?string{
		foreach($this->commands as $command)
			if($command["alias"] == $commandName)
				return $command["usage"];

		return null;
	}

	/**
	 * @param string $commandName
	 *
	 * @return Command|null
	 */
	public function getCommand(string $commandName) : ?Command{
		foreach($this->commands as $command)
			if($command["alias"] == $commandName)
				return new $command["className"]($this);

		return null;
	}

	/**
	 * @return array
	 */
	public function toArrayKV() : array{
		$kv = array();
		foreach($this->commands as $command)
			$kv[$command["alias"]] = array(
				"description" => $command["description"],
				"usage"       => $command["usage"],
			);

		return $kv;
	}

}