# Fantasy Character Factory — Abstract Factory Pattern

## Overview

This project demonstrates the **Abstract Factory** design pattern through a fantasy RPG character creation system.

```
                    CharacterFactory (Interface)
                           │
          ┌────────────────┼────────────────┐
          │                │                │
          ▼                ▼                ▼
     WarriorFactory    MageFactory    ArcherFactory
          │                │                │
     ┌────┼────┐       ┌───┼────┐       ┌───┼────┐
     ▼    ▼    ▼       ▼   ▼    ▼       ▼   ▼    ▼
  Sword Plate Power   Staff Robe Fire   Bow Leather Multi
      Armor Strike       Armor Ball           Armor  Shot
```

**Three Abstract Products:** `Weapon`, `Armor`, `Ability`  
**One Abstract Factory:** `CharacterFactory`  
**Three Concrete Factories:** `WarriorFactory`, `MageFactory`, `ArcherFactory`  
**Nine Concrete Products:** One family of three per factory

---

## Run the Demo

```bash
composer install
php demo.php
```

Output:
```
🏰  FANTASY CHARACTER FACTORY  🏰
Creating party members...

==================================================
Character: Warrior
==================================================
Weapon:  ⚔️  Swings sword with mighty force!
Armor:   🛡️  Dons heavy plate armor. Clank!
Ability: 💥  POWER STRIKE! Devastating blow deals massive damage!

==================================================
Character: Mage
==================================================
Weapon:  🔮  Channels arcane energy through the staff!
Armor:   🧙  Wears flowing wizard robes. Rustle of silk.
Ability: 🔥  FIREBALL! Hurls a blazing sphere of flame!

==================================================
Character: Archer
==================================================
Weapon:  🏹  Draws bowstring taut, releases arrow!
Armor:   🦌  Straps on supple leather armor. Silent movement.
Ability: 🎯  MULTI-SHOT! Looses three arrows at once!
```

---

## 🎓 Laboratory Activity: Add a New Warrior Class

### Objective
Practice the Abstract Factory pattern by adding a **fourth character class** — a **Berserker** warrior variant.

### The Berserker Concept
A fierce, lightly-armored warrior who trades defense for pure offense:
- **Weapon:** Greataxe — massive two-handed axe
- **Armor:** Fur Cloak — minimal protection, maximum mobility
- **Ability:** Rage — enters a frenzy, attacking wildly

### Step-by-Step Instructions

#### 1. Create the Concrete Products

Create three new files in `src/Berserker/`:

**`src/Berserker/Greataxe.php`**
```php
<?php
declare(strict_types=1);
namespace Fantasy\Berserker;
use Fantasy\Contracts\Weapon;

class Greataxe implements Weapon
{
    public function use(): string
    {
        return "🪓  Swings massive greataxe in a deadly arc!";
    }
}
```

**`src/Berserker/FurCloak.php`**
```php
<?php
declare(strict_types=1);
namespace Fantasy\Berserker;
use Fantasy\Contracts\Armor;

class FurCloak implements Armor
{
    public function equip(): string
    {
        return "🦁  Drapes fur cloak over bare shoulders. Primal!";
    }
}
```

**`src/Berserker/Rage.php`**
```php
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
```

#### 2. Create the Concrete Factory

**`src/Berserker/BerserkerFactory.php`**
```php
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
```

#### 3. Update the Demo to Include Berserker

Edit `demo.php` and add:

```php
use Fantasy\Berserker\BerserkerFactory;

// ... inside the demo, after Archer:
createCharacter(new BerserkerFactory(), 'Berserker');
```

#### 4. Test Your Implementation

```bash
composer dump-autoload
php demo.php
```

You should see:
```
==================================================
Character: Berserker
==================================================
Weapon:  🪓  Swings massive greataxe in a deadly arc!
Armor:   🦁  Drapes fur cloak over bare shoulders. Primal!
Ability: 😤  RAGE! Frenzy takes over — unstoppable fury!
```

---

## 🧪 Extension Challenges

### Challenge 1: Add a Paladin (Holy Warrior)
- Weapon: **Holy Mace**
- Armor: **Blessed Plate**
- Ability: **Divine Shield**

### Challenge 2: Add a Necromancer (Dark Mage)
- Weapon: **Bone Wand**
- Armor: **Shroud of Shadows**
- Ability: **Raise Undead**

### Challenge 3: Add a Factory Producer
Create a `CharacterFactoryProducer` class that returns the appropriate factory based on a string:

```php
class CharacterFactoryProducer
{
    public static function createFactory(string $class): CharacterFactory
    {
        return match (strtolower($class)) {
            'warrior'   => new WarriorFactory(),
            'mage'      => new MageFactory(),
            'archer'    => new ArcherFactory(),
            'berserker' => new BerserkerFactory(),
            default     => throw new InvalidArgumentException("Unknown class: $class"),
        };
    }
}
```

Then use it in `demo.php`:
```php
$factory = CharacterFactoryProducer::createFactory('berserker');
createCharacter($factory, 'Berserker');
```

---

## 📐 Pattern Recap

| Component | Role | Example |
|-----------|------|---------|
| `Weapon`, `Armor`, `Ability` | **Abstract Products** | Interfaces defining product behavior |
| `CharacterFactory` | **Abstract Factory** | Declares creation methods for each product |
| `WarriorFactory`, etc. | **Concrete Factories** | Create compatible product families |
| `Sword`, `PlateArmor`, etc. | **Concrete Products** | Actual implementations |
| `demo.php` | **Client** | Uses only abstract interfaces |

**Key Insight:** The client (`demo.php`) never references concrete classes like `Sword` or `Fireball` directly — it only knows the interfaces. This makes it easy to add new families (like `BerserkerFactory`) without changing client code.

---

## 📁 Project Structure

```
src/
  Contracts/
    Weapon.php
    Armor.php
    Ability.php
    CharacterFactory.php
  Warrior/
    Sword.php
    PlateArmor.php
    PowerStrike.php
    WarriorFactory.php
  Mage/
    MagicStaff.php
    WizardRobe.php
    Fireball.php
    MageFactory.php
  Archer/
    Bow.php
    LeatherArmor.php
    MultiShot.php
    ArcherFactory.php
  Berserker/          ← YOUR NEW FOLDER
    Greataxe.php
    FurCloak.php
    Rage.php
    BerserkerFactory.php

demo.php
composer.json
fantasy_factory.puml  ← PlantUML diagram
```

---

## 🎨 View the Class Diagram

Open `fantasy_factory.puml` in:
- [PlantUML Online](https://www.plantuml.com/plantuml/uml/)
- [PlantText](https://www.planttext.com/)
- VS Code with PlantUML extension (Alt+D to preview)

---

**Happy coding! May your factories always produce compatible families.** ⚔️✨

---

## 🔗 Related Pattern: Builder

The parent directory also contains a **Builder** pattern example: `../aufcard.php`.

- Uses `CallingCardBuilder` to fluently configure and generate calling card images.
- Demonstrates step-by-step object construction with validation in `build()`.
- Good contrast: Abstract Factory creates **families of related objects**, while Builder constructs **one complex object** step by step.