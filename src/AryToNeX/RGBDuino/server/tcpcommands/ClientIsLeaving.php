<?php
/**
 * Created by PhpStorm.
 * User: anthony
 * Date: 22/10/18
 * Time: 17.27
 */

namespace AryToNeX\RGBDuino\server\tcpcommands;


class ClientIsLeaving extends TCPCommand{

    public function run() : array{
        $this->owner->setConnectedClient(null);
        echo "TCP: Client left\n";
        return [true];
    }
    
}