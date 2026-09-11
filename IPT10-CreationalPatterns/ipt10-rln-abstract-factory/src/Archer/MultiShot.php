<?php

declare(strict_types=1);

namespace Fantasy\Archer;

use Fantasy\Contracts\Ability;

class MultiShot implements Ability
{
    public function activate(): string
    {
        return "🎯  MULTI-SHOT! Looses three arrows at once!";
    }
}