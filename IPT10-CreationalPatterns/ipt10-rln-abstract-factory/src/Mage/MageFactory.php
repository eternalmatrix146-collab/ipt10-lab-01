<?php

declare(strict_types=1);

namespace Fantasy\Mage;

use Fantasy\Contracts\Armor;
use Fantasy\Contracts\Ability;
use Fantasy\Contracts\CharacterFactory;
use Fantasy\Contracts\Weapon;

class MageFactory implements CharacterFactory
{
    public function createWeapon(): Weapon
    {
        return new MagicStaff();
    }

    public function createArmor(): Armor
    {
        return new WizardRobe();
    }

    public function createAbility(): Ability
    {
        return new Fireball();
    }
}