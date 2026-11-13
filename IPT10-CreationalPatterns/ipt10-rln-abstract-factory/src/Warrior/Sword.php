<?php

declare(strict_types=1);

namespace Fantasy\Warrior;

use Fantasy\Contracts\Weapon;

class Sword implements Weapon
{
    public function use(): string
    {
        return "⚔️  Swings sword with mighty force!";
    }
}