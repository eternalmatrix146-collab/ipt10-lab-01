<?php

declare(strict_types=1);

namespace Fantasy\Contracts;

interface Armor
{
    public function equip(): string;
}