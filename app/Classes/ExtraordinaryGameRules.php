<?php

namespace App\Classes;

class ExtraordinaryGameRules extends OrdinaryGameRules {
    public function getFieldLength(): int
    {
        return 16;
    }

    public function getID(): string
    {
        return 'extraordinary';
    }
}
