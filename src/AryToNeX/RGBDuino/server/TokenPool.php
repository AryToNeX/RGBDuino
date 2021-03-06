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

class TokenPool{

    /** @var Token[] */
    private $pool;

    public function enroll(Token $token) : bool{
        if(isset($this->pool[$token->getToken()])) return false;
        $this->pool[$token->getToken()] = $token;
        return true;
    }

    public function disenroll(Token $token) : bool{
        return $this->disenrollToken($token->getToken());
    }

    public function disenrollToken(string $token) : bool{
        if(!isset($this->pool[$token])) return false;
        unset($this->pool[$token]);
        return true;
    }

    public function getByToken(string $token) : ?Token{
        return $this->pool[$token];
    }

    public function asArray() : array{
        return $this->pool;
    }

}