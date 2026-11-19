<?php

declare(strict_types=1);

namespace Fantasy\Contracts;

interface Weapon
{
    public function use(): string;
}