<?php

declare(strict_types=1);

namespace Fantasy\Berserker;

use Fantasy\Contracts\Ability;

class Rage implements Ability
{
    public function activate(): string
    {
        return "😤  RAGE! Frenzy takes over — unstoppable fury!";
    }
}
