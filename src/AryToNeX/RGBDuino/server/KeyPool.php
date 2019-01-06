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


class KeyPool{

    private $pool;

    public function enroll(ClientKey $key) : bool{
        if(isset($this->pool[$key->getKey()])) return false;
        $this->pool[$key->getKey()] = $key;
        return true;
    }

    public function disenroll(ClientKey $key) : bool{
        return $this->disenrollKey($key->getKey());
    }

    public function disenrollKey(string $key) : bool{
        if(!isset($this->pool[$key])) return false;
        unset($this->pool[$key]);
        return true;
    }

    public function getByKey(string $key) : ?ClientKey{
        return $this->pool[$key];
    }

    public function asArray() : array{
        return $this->pool;
    }

}