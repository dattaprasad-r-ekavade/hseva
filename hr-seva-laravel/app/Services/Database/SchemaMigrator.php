<?php

namespace App\Services\Database;

use App\Services\Tenant\TenantManager;
use Illuminate\Support\Facades\DB;

class SchemaMigrator
{
    /** @var array<int, class-string> */
    private array $migrations = [
        1 => \Database\Migrations\Hr\CreateHrMigrationsTable::class,
        2 => \Database\Migrations\Hr\CreateHrCoreSchema::class,
        3 => \Database\Migrations\Hr\CreateHrNormalizedSchema::class,
    ];

    public function __construct(private TenantManager $tenants) {}

    public function runCentral(): void
    {
        if ($this->tenants->centralDriver() !== 'mysql') {
            $path = $this->tenants->centralPath();
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            if (! file_exists($path)) {
                touch($path);
            }
        } else {
            $this->tenants->ensureCentralDatabase();
        }

        $this->runOnConnection($this->tenants->central());
    }

    public function runTenant(int $clientId): void
    {
        $this->tenants->ensureTenantDatabase($clientId);
        $this->tenants->setClientId($clientId);
        $this->runOnConnection($this->tenants->tenant());
        face_attendance_settings_seed($this->tenants->tenant()->getPdo());
    }

    private function runOnConnection($connection): void
    {
        $pdo = $connection->getPdo();
        foreach ($this->migrations as $version => $class) {
            if ($this->hasRun($connection, $version)) {
                continue;
            }
            (new $class)->up($pdo);
            $connection->table('hr_migrations')->insert([
                'version' => $version,
                'name' => class_basename($class),
                'ran_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ]);
        }
    }

    private function hasRun($connection, int $version): bool
    {
        if (! $this->tableExists($connection, 'hr_migrations')) {
            return false;
        }

        return $connection->table('hr_migrations')->where('version', $version)->exists();
    }

    private function tableExists($connection, string $table): bool
    {
        $driver = $connection->getDriverName();
        if ($driver === 'mysql') {
            $rows = $connection->select(
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table]
            );

            return count($rows) > 0;
        }

        $rows = $connection->select("SELECT name FROM sqlite_master WHERE type='table' AND name=?", [$table]);

        return count($rows) > 0;
    }
}
