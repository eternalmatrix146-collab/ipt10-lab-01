<?php

declare(strict_types=1);

namespace Fantasy\Warrior;

use Fantasy\Contracts\Armor;

class PlateArmor implements Armor
{
    public function equip(): string
    {
        return "🛡️  Dons heavy plate armor. Clank!";
    }
}