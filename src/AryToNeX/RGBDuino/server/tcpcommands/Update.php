<?php
/**
 * Created by PhpStorm.
 * User: anthony
 * Date: 22/10/18
 * Time: 17.20
 */

namespace AryToNeX\RGBDuino\server\tcpcommands;

use AryToNeX\RGBDuino\server\Updater;

class Update extends TCPCommand{

    public function run(): array{
        $updater = new Updater();
        echo "TCP: Trying to update server...\n";
        if($updater->update()){
            echo "Update successful; restarting...\n";
            $this->owner->setShouldExit(2);
            $updateStatus = true;
        }else{
            echo "Update not necessary\n";
            $updateStatus = false;
        }
        return [true, $updateStatus];
    }

}