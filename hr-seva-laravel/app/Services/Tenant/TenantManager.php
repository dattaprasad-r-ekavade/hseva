<?php

namespace App\Services\Tenant;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantManager
{
    private ?int $clientId = null;

    public function centralDriver(): string
    {
        return (string) config('hrseva.central_driver', 'sqlite');
    }

    public function tenantDriver(): string
    {
        return (string) config('hrseva.tenant_driver', 'sqlite');
    }

    public function usesMysql(): bool
    {
        return $this->centralDriver() === 'mysql' || $this->tenantDriver() === 'mysql';
    }

    public function centralPath(): string
    {
        return (string) config('hrseva.central_db_path');
    }

    public function tenantPath(int $clientId): string
    {
        return rtrim((string) config('hrseva.storage_dir'), '/').'/tenant_'.$clientId.'/app.db';
    }

    public function tenantDatabaseName(int $clientId): string
    {
        return (string) config('hrseva.mysql.tenant_database_prefix').$clientId;
    }

    public function setClientId(?int $clientId): void
    {
        $this->clientId = $clientId && $clientId > 0 ? $clientId : null;
        if ($this->clientId) {
            $this->configureTenantConnection($this->clientId);
        }
    }

    public function clientId(): ?int
    {
        return $this->clientId;
    }

    public function central()
    {
        $this->configureCentralConnection();

        return DB::connection('central');
    }

    public function tenant()
    {
        if (! $this->clientId) {
            return $this->central();
        }
        $this->configureTenantConnection($this->clientId);

        return DB::connection('tenant');
    }

    public function ensureTenantDatabase(int $clientId): void
    {
        if ($this->tenantDriver() === 'mysql') {
            $this->ensureMysqlDatabase($this->tenantDatabaseName($clientId));
            $this->setClientId($clientId);
            init_schema($this->tenant()->getPdo());
            face_attendance_settings_seed($this->tenant()->getPdo());

            return;
        }

        $path = $this->tenantPath($clientId);
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (! file_exists($path)) {
            touch($path);
        }
        $this->setClientId($clientId);
        init_schema($this->tenant()->getPdo());
        face_attendance_settings_seed($this->tenant()->getPdo());
    }

    public function ensureCentralDatabase(): void
    {
        if ($this->centralDriver() === 'mysql') {
            $this->ensureMysqlDatabase((string) config('hrseva.mysql.central_database'));

            return;
        }

        $path = $this->centralPath();
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        if (! file_exists($path)) {
            touch($path);
        }
    }

    private function configureCentralConnection(): void
    {
        if ($this->centralDriver() === 'mysql') {
            Config::set('database.connections.central', $this->mysqlConnection(
                (string) config('hrseva.mysql.central_database')
            ));
        } else {
            Config::set('database.connections.central.database', $this->centralPath());
        }
        DB::purge('central');
    }

    private function configureTenantConnection(int $clientId): void
    {
        if ($this->tenantDriver() === 'mysql') {
            Config::set('database.connections.tenant', $this->mysqlConnection(
                $this->tenantDatabaseName($clientId)
            ));
        } else {
            $path = $this->tenantPath($clientId);
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            if (! file_exists($path)) {
                touch($path);
            }
            Config::set('database.connections.tenant.database', $path);
        }
        DB::purge('tenant');
    }

    private function mysqlConnection(string $database): array
    {
        return [
            'driver' => 'mysql',
            'host' => config('hrseva.mysql.host'),
            'port' => config('hrseva.mysql.port'),
            'database' => $database,
            'username' => config('hrseva.mysql.username'),
            'password' => config('hrseva.mysql.password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ];
    }

    private function ensureMysqlDatabase(string $database): void
    {
        $bootstrap = $this->mysqlConnection('mysql');
        Config::set('database.connections.hr_mysql_bootstrap', $bootstrap);
        DB::purge('hr_mysql_bootstrap');
        DB::connection('hr_mysql_bootstrap')->statement(
            'CREATE DATABASE IF NOT EXISTS `'.str_replace('`', '``', $database).'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }
}
