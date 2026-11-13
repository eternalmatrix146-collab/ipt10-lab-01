<?php

declare(strict_types=1);

namespace Fantasy\Mage;

use Fantasy\Contracts\Armor;

class WizardRobe implements Armor
{
    public function equip(): string
    {
        return "🧙  Wears flowing wizard robes. Rustle of silk.";
    }
}