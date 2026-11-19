<?php

declare(strict_types=1);

namespace Fantasy\Archer;

use Fantasy\Contracts\Weapon;

class Bow implements Weapon
{
    public function use(): string
    {
        return "🏹  Draws bowstring taut, releases arrow!";
    }
}