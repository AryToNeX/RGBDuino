<?php
/**
 * Created by PhpStorm.
 * User: anthony
 * Date: 22/10/18
 * Time: 17.22
 */

namespace AryToNeX\RGBDuino\server\tcpcommands;


class ClientIsHere extends TCPCommand{

    public function run(): array
    {
        $this->owner->setConnectedClient($this->ip);
        echo "TCP: Client is here\n";
        return [true];
    }

}