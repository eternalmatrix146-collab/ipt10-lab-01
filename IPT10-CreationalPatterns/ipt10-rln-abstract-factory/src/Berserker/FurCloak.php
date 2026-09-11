<?php

declare(strict_types=1);

namespace Fantasy\Berserker;

use Fantasy\Contracts\Armor;

class FurCloak implements Armor
{
    public function equip(): string
    {
        return "🦁  Drapes fur cloak over bare shoulders. Primal!";
    }
}
