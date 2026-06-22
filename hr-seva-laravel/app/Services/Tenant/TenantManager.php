<?php

namespace App\Services\Tenant;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantManager
{
    private ?int $clientId = null;

    public function centralPath(): string
    {
        return (string) config('hrseva.central_db_path');
    }

    public function tenantPath(int $clientId): string
    {
        return rtrim((string) config('hrseva.storage_dir'), '/').'/tenant_'.$clientId.'/app.db';
    }

    public function setClientId(?int $clientId): void
    {
        $this->clientId = $clientId && $clientId > 0 ? $clientId : null;
        if ($this->clientId) {
            $path = $this->tenantPath($this->clientId);
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            Config::set('database.connections.tenant.database', $path);
            DB::purge('tenant');
        }
    }

    public function clientId(): ?int
    {
        return $this->clientId;
    }

    public function central()
    {
        Config::set('database.connections.central.database', $this->centralPath());
        DB::purge('central');

        return DB::connection('central');
    }

    public function tenant()
    {
        if (! $this->clientId) {
            return $this->central();
        }
        $this->setClientId($this->clientId);

        return DB::connection('tenant');
    }

    public function ensureTenantDatabase(int $clientId): void
    {
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
}
