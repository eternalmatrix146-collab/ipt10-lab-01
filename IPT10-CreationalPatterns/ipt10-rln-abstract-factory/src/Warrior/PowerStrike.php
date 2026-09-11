<?php

declare(strict_types=1);

namespace Fantasy\Warrior;

use Fantasy\Contracts\Ability;

class PowerStrike implements Ability
{
    public function activate(): string
    {
        return "💥  POWER STRIKE! Devastating blow deals massive damage!";
    }
}