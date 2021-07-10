<?php

namespace App\Classes;

interface GameObjectInterface {
    public function init(array $data) : array;

    public function update(array $data) : array;
}
