<?php

namespace App\Services\Database;

use PDO;

class HrSchemaInstaller
{
    /** @var array<int, class-string> */
    private static array $migrations = [
        1 => \Database\Migrations\Hr\CreateHrMigrationsTable::class,
        2 => \Database\Migrations\Hr\CreateHrCoreSchema::class,
        3 => \Database\Migrations\Hr\CreateHrNormalizedSchema::class,
        4 => \Database\Migrations\Hr\CreateHrShiftSchema::class,
    ];

    public static function install(PDO $pdo): void
    {
        $installer = new self;

        foreach (self::$migrations as $version => $class) {
            if ($installer->hasRun($pdo, $version)) {
                continue;
            }
            (new $class)->up($pdo);
            $installer->record($pdo, $version, class_basename($class));
        }
    }

    private function hasRun(PDO $pdo, int $version): bool
    {
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $st = $pdo->query("SHOW TABLES LIKE 'hr_migrations'");
            if (! $st || ! $st->fetch()) {
                return false;
            }
        } else {
            $st = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='hr_migrations'");
            if (! $st || ! $st->fetch()) {
                return false;
            }
        }

        $st = $pdo->prepare('SELECT 1 FROM hr_migrations WHERE version = ? LIMIT 1');
        $st->execute([$version]);

        return (bool) $st->fetchColumn();
    }

    private function record(PDO $pdo, int $version, string $name): void
    {
        $st = $pdo->prepare('INSERT INTO hr_migrations (version, name, ran_at) VALUES (?, ?, ?)');
        $st->execute([$version, $name, gmdate('Y-m-d\TH:i:s\Z')]);
    }
}
