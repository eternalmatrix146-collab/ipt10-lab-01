<?php

declare(strict_types=1);

namespace Fantasy\Archer;

use Fantasy\Contracts\Armor;

class LeatherArmor implements Armor
{
    public function equip(): string
    {
        return "🦌  Straps on supple leather armor. Silent movement.";
    }
}