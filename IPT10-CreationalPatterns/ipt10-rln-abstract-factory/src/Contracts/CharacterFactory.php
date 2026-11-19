<?php

declare(strict_types=1);

namespace Fantasy\Contracts;

interface CharacterFactory
{
    public function createWeapon(): Weapon;
    public function createArmor(): Armor;
    public function createAbility(): Ability;
}