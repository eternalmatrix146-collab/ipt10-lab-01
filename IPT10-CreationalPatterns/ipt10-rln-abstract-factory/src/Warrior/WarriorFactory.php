<?php

declare(strict_types=1);

namespace Fantasy\Warrior;

use Fantasy\Contracts\Armor;
use Fantasy\Contracts\Ability;
use Fantasy\Contracts\CharacterFactory;
use Fantasy\Contracts\Weapon;

class WarriorFactory implements CharacterFactory
{
    public function createWeapon(): Weapon
    {
        return new Sword();
    }

    public function createArmor(): Armor
    {
        return new PlateArmor();
    }

    public function createAbility(): Ability
    {
        return new PowerStrike();
    }
}