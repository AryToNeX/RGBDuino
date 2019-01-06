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

use AryToNeX\RGBDuino\server\tcpcommands\TCPCommand;

class TCPManager{

	/*
	 * AUTHENTICATION:
	 *   {"shouldEnroll": bool, "token": "TOKEN", "clientKey": "KEY"}
	 *   shouldEnroll -> true for enroll, false for disenroll
	 * REQUEST:
	 *   {"method": "methodName", "arguments": {ARRAY OF ARGS}, "token": "TOKEN"}
	 * RESPONSE:
	 *   {"ok": bool, "responseDetails": {DETAILS}}
	 */

    /** @var resource */
    protected $sock;
    /** @var Status */
    protected $owner;
    /** @var int */
    protected $port;
    /** @var TokenPool */
    protected $tokenPool;
    /** @var int|null */
    protected $lastCommandTime;
    /** @var array */
    protected $availableCommands = array();

    /**
     * TCPManager constructor.
     *
     * @param Status $owner
     * @param int    $port
     */
    public function __construct(Status $owner, int $port){
        $this->owner = $owner;

        // constitute list of all commands
        foreach(scandir(__DIR__ . "/tcpcommands/") as $tcpClass){
            $className = pathinfo($tcpClass, PATHINFO_FILENAME);
            try {
                $reflectionClass = new \ReflectionClass("AryToNeX\RGBDuino\server\\tcpcommands\\" . $className);
            } catch (\ReflectionException $e){
                continue;
            }

            if(!$reflectionClass->isAbstract())
                $availableCommands[$className] = "AryToNeX\RGBDuino\server\\tcpcommands\\" . $className;
        }

        $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->port = $port;
        socket_set_nonblock($this->sock);
        socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->sock, "0.0.0.0", $port);
        socket_listen($this->sock);
        echo "TCP Socket listening on port $port\n";
    }

    /**
     * @return int
     */
    public function getPort() : int{
        return $this->port;
    }

    /**
     * @return int|null
     */
    public function getLastCommandTime() : ?int{
        return $this->lastCommandTime;
    }

    public function receiveTcp() : array{
        $accept = socket_accept($this->sock);
        if(is_resource($accept)){
            socket_getpeername($accept, $ip, $port);
            echo "TCP: Connection from $ip:$port\n";
            $command = json_decode(Utils::socket_read_until($accept, "\n"));

            // AUTH
            if(isset($command["shouldEnroll"])){
                if($command["shouldEnroll"]) {
                    return ["ok" => $this->performAuth($command["clientKey"], $command["token"])]; // change
                }else{
                    return ["ok" => $this->tokenPool->disenrollToken($command["token"])]; // change
                }
            }

            // COMMANDS
            if(isset($command["token"]) && isset($command["method"]) && isset($command["arguments"])){
                // VALIDATE TOKEN
                if($this->tokenPool->getByToken($command["token"]) !== null){
                    echo "TCP: Connection from " .
                        $this->tokenPool->getByToken($command["token"])->getOwner()->getClientName() . ". Serving...\n";
                    /** @var TCPCommand $cmd */
                    if(isset($this->availableCommands[$command["method"]])) {
                        $cmd = new $this->availableCommands[$command["method"]]($this->owner, $ip, $port, $command["arguments"]);
                        $ret = $cmd->run();
                    }else{
                        echo "TCP: Command not found.\n";
                        $ret = [false, "COMMAND_NOT_FOUND"];
                    }
                }else{
                    echo "TCP: Connection from foreigner! Ignoring...\n";
                    $ret = [false, "YOU_ARE_NOT_ALLOWED"];
                }
            }

            socket_write($accept, json_encode(["ok" => $ret[0], "responseDetails" => ($ret[1] ?? null)]) . "\n");
            socket_close($accept);
            if($this->owner->getConnectedClient() !== null && $ip === $this->owner->getConnectedClient())
                $this->lastCommandTime = time();

            echo "TCP: Connection from $ip:$port closed.\n";
            return ["ok" => $ret[0], "faderShouldStop" => ($ret[2] ?? false)];
        }
        return ["ok" => false];
    }

    private function performAuth($clientKey, $token){
        if(($key = $this->owner->getKeyPool()->getByKey($clientKey)) === null) return false;

        $this->tokenPool->enroll(new Token($token, $key));
        return true;
    }

    public function pingClient() : bool{
        if($this->owner->getConnectedClient() === null) return false;

        if(!$sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP))
            return false;
        if(!@socket_connect($sock, $this->owner->getConnectedClient(), $this->port + 1))
            return false;

        socket_write($sock, "ping\n");
        $str = Utils::socket_read_until($sock, "\n");
        socket_close($sock);

        if($str === "PONG"){
            $this->lastCommandTime = time();

            return true;
        }

        return false;
    }

    public function close() : void{
        $linger = array('l_linger' => 0, 'l_onoff' => 1);
        socket_set_option($this->sock, SOL_SOCKET, SO_LINGER, $linger);
        socket_shutdown($this->sock);
        socket_close($this->sock);
    }

}