<?php

declare(strict_types=1);

namespace Fantasy\Mage;

use Fantasy\Contracts\Ability;

class Fireball implements Ability
{
    public function activate(): string
    {
        return "🔥  FIREBALL! Hurls a blazing sphere of flame!";
    }
}