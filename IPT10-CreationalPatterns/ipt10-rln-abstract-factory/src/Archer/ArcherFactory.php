<?php

declare(strict_types=1);

namespace Fantasy\Archer;

use Fantasy\Contracts\Armor;
use Fantasy\Contracts\Ability;
use Fantasy\Contracts\CharacterFactory;
use Fantasy\Contracts\Weapon;

class ArcherFactory implements CharacterFactory
{
    public function createWeapon(): Weapon
    {
        return new Bow();
    }

    public function createArmor(): Armor
    {
        return new LeatherArmor();
    }

    public function createAbility(): Ability
    {
        return new MultiShot();
    }
}