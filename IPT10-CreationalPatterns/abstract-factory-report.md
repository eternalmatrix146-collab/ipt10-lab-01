# Calling Card Generator — Builder Pattern Report

## Work completed

Refactored `aufcard.php` to use the **Builder** design pattern for generating student calling cards.

- Added `CallingCardBuilder` to configure card properties fluently before construction.
- Refactored `CallingCard` to hold configurable state (`businessName`, `position`, `street`, `city`) and removed the Prototype `__clone()` logic.
- Updated the main loop to use `$builder->setStudent($student)->build()` instead of `clone $prototype`.

## Structure

```text
CallingCardBuilder (fluent builder)
    └── build() → CallingCard (product)
            ├── setStudent()
            ├── setBusinessName()
            ├── setPosition()
            ├── setAddress()
            ├── render()
            ├── save()
            └── destroy()
```

## Class relationships

- `CallingCardBuilder` collects all card configuration through chained setter methods and validates required fields in `build()`.
- `CallingCard` is the final product. It owns the GD canvas, color palette, rendering logic, and resource cleanup.
- The builder centralizes defaults and validation, so the client does not need to know about required fields or canvas initialization.

## Verification

- PHP syntax validation passed.
- Cards generated successfully using the builder workflow.

## Setup (Windows — enable PHP GD)

Run these commands in **Command Prompt** or **PowerShell**:

```cmd
rem 1. Find your PHP installation folder (example paths shown)
where php

rem 2. Open your PHP folder and the ext subfolder
cd C:\php\ext

rem 3. Download the matching GD DLL for your PHP version
rem    Example for PHP 8.2 x64 NTS:
curl -o php_gd.zip https://windows.php.net/downloads/pecl/releases/gd/8.2.0/php_gd-8.2.0-nts-vs16-x64.zip
tar -xf php_gd.zip
copy php_gd.dll C:\php\ext\

rem 4. Enable the extension in php.ini
rem    Open php.ini, find the line below, and remove the semicolon:
rem    ;extension=gd
rem    Change it to:
rem    extension=gd

rem 5. Verify GD is loaded
php -r "var_dump(extension_loaded('gd'));"

rem 6. Run the card generator
cd C:\Design Pattern
php aufcard.php
```

Notes:
- Replace `C:\php` with your actual PHP root if it differs.
- Use the exact GD build that matches your PHP version, architecture (`x64`), and thread safety (`nts` or `ts`). You can confirm with `php -i | findstr "Compiler"` and `php -i | findstr "Architecture"`.
- After editing `php.ini`, restart any web server if you also use PHP via Apache/IIS.

---

# Abstract Factory Extension Report

## Work completed

Extended the `ipt10-rln-abstract-factory` project with one new warrior class: **Berserker**.

Added the Berserker product family:

- `src/Berserker/Greataxe.php` implements `Fantasy\Contracts\Weapon`
- `src/Berserker/FurCloak.php` implements `Fantasy\Contracts\Armor`
- `src/Berserker/Rage.php` implements `Fantasy\Contracts\Ability`
- `src/Berserker/BerserkerFactory.php` implements `Fantasy\Contracts\CharacterFactory`

Updated `demo.php` so the client creates and displays a Berserker through the existing `createCharacter()` workflow.

## Structure analysis

The project follows the Abstract Factory pattern:

```text
CharacterFactory
├── WarriorFactory
│   ├── Sword
│   ├── PlateArmor
│   └── PowerStrike
├── MageFactory
│   ├── MagicStaff
│   ├── WizardRobe
│   └── Fireball
├── ArcherFactory
│   ├── Bow
│   ├── LeatherArmor
│   └── MultiShot
└── BerserkerFactory
    ├── Greataxe
    ├── FurCloak
    └── Rage
```

## Class relationships

- `Weapon`, `Armor`, and `Ability` are abstract product contracts.
- `CharacterFactory` is the abstract factory contract and declares one creation method for each product type.
- Each concrete factory implements `CharacterFactory` and creates one compatible product family.
- `BerserkerFactory` returns `Greataxe`, `FurCloak`, and `Rage`.
- `demo.php` is the client. It depends on `CharacterFactory` and product interfaces rather than concrete product classes.
- Composer PSR-4 autoload maps the `Fantasy\\` namespace to `src/`, so the new `Fantasy\Berserker` classes are loaded automatically.

## Verification

- PHP syntax validation passed for the updated source and demo files.
- The demo was run successfully and generated the Berserker output.
