<?php

namespace App\Classes;

use Illuminate\Support\Str;
use ReflectionClass;

abstract class GameType {
    abstract public function getFieldLength() : int;

    abstract public function getStartPositions() : array;

    static public function getInstanceByTypeName(string $typeName) : GameType {
        return new ("App\\Classes\\" . ucfirst($typeName) . "GameType")();
    }

    public function getID() : string {
        return Str::of((new ReflectionClass($this))->getShortName())->lower()->replace('gametype', '');
    }
}
