<?php

declare(strict_types=1);

namespace Fantasy\Contracts;

interface Ability
{
    public function activate(): string;
}