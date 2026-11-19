<?php

declare(strict_types=1);

namespace Fantasy\Mage;

use Fantasy\Contracts\Weapon;

class MagicStaff implements Weapon
{
    public function use(): string
    {
        return "🔮  Channels arcane energy through the staff!";
    }
}