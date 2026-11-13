<?php

declare(strict_types=1);

namespace Fantasy\Berserker;

use Fantasy\Contracts\Weapon;

class Greataxe implements Weapon
{
    public function use(): string
    {
        return "🪓  Swings massive greataxe in a deadly arc!";
    }
}
