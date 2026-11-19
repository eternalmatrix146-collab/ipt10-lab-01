<?php

declare(strict_types=1);

namespace Phantom\Database;

/**
 * Base class for files in database/migrations. Each file returns an
 * anonymous subclass:
 *
 *   return new class extends Migration {
 *       public function up(Connection $db): void { $db->unprepared('CREATE TABLE ...'); }
 *       public function down(Connection $db): void { $db->unprepared('DROP TABLE ...'); }
 *   };
 */
abstract class Migration
{
    abstract public function up(Connection $db): void;

    abstract public function down(Connection $db): void;
}
