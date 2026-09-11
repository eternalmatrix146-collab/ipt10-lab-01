<?php

declare(strict_types=1);

namespace Fantasy\Berserker;

use Fantasy\Contracts\Armor;
use Fantasy\Contracts\Ability;
use Fantasy\Contracts\CharacterFactory;
use Fantasy\Contracts\Weapon;

class BerserkerFactory implements CharacterFactory
{
    public function createWeapon(): Weapon
    {
        return new Greataxe();
    }

    public function createArmor(): Armor
    {
        return new FurCloak();
    }

    public function createAbility(): Ability
    {
        return new Rage();
    }
}
