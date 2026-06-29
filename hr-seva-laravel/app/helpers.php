<?php

use App\Exceptions\LegacyApiResponseException;
use App\Services\Database\NormalizedSchemaInstaller;
use App\Services\Employees\EmployeeRepository;
use App\Services\Storage\TenantSettingsService;
use App\Support\HrDatabase;
use App\Support\HrHelpers;
use App\Support\HrRequestContext;
use App\Support\HrStorage;

if (! function_exists('bad')) {
    function bad(string $message): never
    {
        throw new LegacyApiResponseException(['detail' => $message], 400);
    }
}

if (! function_exists('j')) {
    function j(mixed $payload, int $status = 200): never
    {
        throw new LegacyApiResponseException($payload, $status);
    }
}

if (! function_exists('nf')) {
    function nf(string $message): never
    {
        j(['detail' => $message], 404);
    }
}

if (! function_exists('now_iso')) {
    function now_iso(): string
    {
        return HrHelpers::nowIso();
    }
}

if (! function_exists('s')) {
    function s(mixed $value, string $default = ''): string
    {
        return HrHelpers::s($value, $default);
    }
}

if (! function_exists('up')) {
    function up(mixed $value): string
    {
        return HrHelpers::up($value);
    }
}

if (! function_exists('f')) {
    function f(mixed $value, float $default = 0.0): float
    {
        return HrHelpers::f($value, $default);
    }
}

if (! function_exists('b')) {
    function b(mixed $value): bool
    {
        return HrHelpers::b($value);
    }
}

if (! function_exists('req_client_id')) {
    function req_client_id(): int
    {
        return HrRequestContext::clientId();
    }
}

if (! function_exists('db_reset_pool')) {
    function db_reset_pool(): void
    {
        HrDatabase::resetPool();
    }
}

if (! function_exists('db_path_for_client')) {
    function db_path_for_client(int $clientId): string
    {
        return HrDatabase::pathForClient($clientId);
    }
}

if (! function_exists('db_open')) {
    function db_open(string $path): \PDO
    {
        return HrDatabase::open($path);
    }
}

if (! function_exists('central_db')) {
    function central_db(): \PDO
    {
        return HrDatabase::central();
    }
}

if (! function_exists('db')) {
    function db(): \PDO
    {
        return HrDatabase::tenant();
    }
}

if (! function_exists('hr_init_normalized_schema')) {
    function hr_init_normalized_schema(\PDO $connection): void
    {
        if (function_exists('app') && app()->bound(NormalizedSchemaInstaller::class)) {
            app(NormalizedSchemaInstaller::class)->install($connection);

            return;
        }

        (new NormalizedSchemaInstaller)->install($connection);
    }
}

if (! function_exists('kv_get')) {
    function kv_get(string $key, mixed $default = null): mixed
    {
        return HrStorage::kvGet($key, $default);
    }
}

if (! function_exists('kv_set')) {
    function kv_set(string $key, mixed $value): void
    {
        HrStorage::kvSet($key, $value);
    }
}

if (! function_exists('kv_set_on')) {
    function kv_set_on(\PDO $connection, string $key, mixed $value): void
    {
        HrStorage::kvSetOn($connection, $key, $value);
    }
}

if (! function_exists('idx')) {
    function idx(string $key): array
    {
        return HrStorage::index($key);
    }
}

if (! function_exists('period')) {
    function period(int $month, int $year): string
    {
        return HrStorage::period($month, $year);
    }
}

if (! function_exists('idkey')) {
    function idkey(string $prefix, string $id): string
    {
        return HrStorage::idKey($prefix, $id);
    }
}

if (! function_exists('find_period')) {
    function find_period(array $rows, int $month, int $year): ?array
    {
        return HrStorage::findPeriod($rows, $month, $year);
    }
}

if (! function_exists('get_sheet')) {
    function get_sheet(string $key, string $message): array
    {
        return HrStorage::getSheet($key, $message);
    }
}

if (! function_exists('del_sheet')) {
    function del_sheet(string $prefix, string $id): void
    {
        HrStorage::deleteSheet($prefix, $id);
    }
}

if (! function_exists('clr_sheet')) {
    function clr_sheet(string $prefix): void
    {
        HrStorage::clearSheet($prefix);
    }
}

if (! function_exists('control_get')) {
    function control_get(): array
    {
        return app(TenantSettingsService::class)->getControlSettings();
    }
}

if (! function_exists('profile_get')) {
    function profile_get(): array
    {
        return app(TenantSettingsService::class)->getCompanyProfile();
    }
}

if (! function_exists('employees_all')) {
    function employees_all(): array
    {
        return app(EmployeeRepository::class)->all();
    }
}

if (! function_exists('employees_active_all')) {
    function employees_active_all(): array
    {
        return app(EmployeeRepository::class)->activeAll();
    }
}

if (! function_exists('employee_is_active')) {
    function employee_is_active(array $employee): bool
    {
        return app(EmployeeRepository::class)->isActive($employee);
    }
}
