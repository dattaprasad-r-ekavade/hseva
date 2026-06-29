<?php

namespace App\Support;

use App\Services\Tenant\TenantManager;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class HrDatabase
{
    /** @var array<string, PDO> */
    private static array $pool = [];

    public static function centralPath(): string
    {
        return (string) config('hrseva.central_db_path');
    }

    public static function tenantPath(int $clientId): string
    {
        return rtrim((string) config('hrseva.storage_dir'), '/').'/tenant_'.$clientId.'/app.db';
    }

    public static function pathForClient(int $clientId): string
    {
        return $clientId > 0 ? self::tenantPath($clientId) : self::centralPath();
    }

    public static function resetPool(): void
    {
        self::$pool = [];

        try {
            DB::purge('central');
            DB::purge('tenant');
        } catch (Throwable) {
        }
    }

    public static function open(string $path): PDO
    {
        if (isset(self::$pool[$path])) {
            return self::$pool[$path];
        }

        if (function_exists('app') && app()->bound(TenantManager::class)) {
            $manager = app(TenantManager::class);
            if ($path === self::centralPath()) {
                if ($manager->centralDriver() === 'mysql' || file_exists($path)) {
                    self::$pool[$path] = $manager->central()->getPdo();

                    return self::$pool[$path];
                }
            }

            if (preg_match('/tenant_(\d+)\/app\.db$/', $path, $matches)) {
                $clientId = (int) $matches[1];
                if ($clientId > 0 && ($manager->tenantDriver() === 'mysql' || file_exists($path))) {
                    $manager->setClientId($clientId);
                    self::$pool[$path] = $manager->tenant()->getPdo();

                    return self::$pool[$path];
                }
            }
        }

        $directory = dirname($path);
        if (! is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        $pdo = new PDO('sqlite:'.$path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$pool[$path] = $pdo;

        return $pdo;
    }

    public static function central(): PDO
    {
        return self::open(self::centralPath());
    }

    public static function tenant(): PDO
    {
        return self::open(self::pathForClient(HrRequestContext::clientId()));
    }
}
