<?php
/**
 * Fantasy Character Factory — Abstract Factory Pattern Demo
 *
 * Pattern: Abstract Factory
 * - 3 Product Families: Weapon, Armor, Ability
 * - 3 Concrete Factories: WarriorFactory, MageFactory, ArcherFactory
 * - 9 Concrete Products
 *
 * Run: php demo.php
 */

require __DIR__ . '/vendor/autoload.php';

use Fantasy\Archer\ArcherFactory;
use Fantasy\Berserker\BerserkerFactory;
use Fantasy\Contracts\CharacterFactory;
use Fantasy\Mage\MageFactory;
use Fantasy\Warrior\WarriorFactory;

function createCharacter(CharacterFactory $factory, string $className): void
{
    $weapon = $factory->createWeapon();
    $armor = $factory->createArmor();
    $ability = $factory->createAbility();

    echo "\n" . str_repeat('=', 50) . "\n";
    echo "Character: $className\n";
    echo str_repeat('=', 50) . "\n";
    echo "Weapon:  " . $weapon->use() . "\n";
    echo "Armor:   " . $armor->equip() . "\n";
    echo "Ability: " . $ability->activate() . "\n";
}

// Demo: Create all three character types
echo "🏰  FANTASY CHARACTER FACTORY  🏰\n";
echo "Creating party members...\n";

createCharacter(new WarriorFactory(), 'Warrior');
createCharacter(new MageFactory(), 'Mage');
createCharacter(new ArcherFactory(), 'Archer');
createCharacter(new BerserkerFactory(), 'Berserker');

echo "\n" . str_repeat('=', 50) . "\n";
echo "Party assembled! Ready for adventure. 🗡️✨\n";