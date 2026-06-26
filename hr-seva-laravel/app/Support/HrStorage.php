<?php

namespace App\Support;

use App\Services\Storage\SheetStorageService;
use App\Services\Storage\TenantSettingsService;
use App\Services\Tenant\TenantManager;
use PDO;

class HrStorage
{
    /** @var list<string> */
    private const SHEET_TYPES = [
        'attendance_sheet', 'payroll_sheet', 'pf_sheet', 'pf_return_sheet', 'esic_sheet',
        'esic_return_sheet', 'ecr_sheet', 'fnf_sheet', 'gratuity_sheet', 'bonus_sheet', 'payslip',
    ];

    public static function kvGet(string $key, mixed $default = null): mixed
    {
        if (function_exists('app') && app()->bound(SheetStorageService::class)) {
            $service = app(SheetStorageService::class);
            if ($key === 'payroll_overrides') {
                return $service->payrollOverrides();
            }
            if (preg_match('/^attendance_daily_(\d{4})-(\d{2})$/', $key, $matches)) {
                return $service->attendanceDaily((int) $matches[2], (int) $matches[1]);
            }
            if (str_ends_with($key, '_index')) {
                $prefix = str_replace('_index', '', $key);
                if (in_array($prefix, self::SHEET_TYPES, true)) {
                    return $service->index($prefix);
                }
            }
            if (preg_match('/^(attendance_sheet|payroll_sheet|pf_sheet|pf_return_sheet|esic_sheet|esic_return_sheet|ecr_sheet|fnf_sheet|gratuity_sheet|bonus_sheet|payslip)_(.+)$/', $key, $matches)) {
                return $service->get($matches[1], $matches[2]) ?? $default;
            }
            if (in_array($key, ['control_settings', 'company_profile'], true) && app()->bound(TenantSettingsService::class)) {
                return app(TenantSettingsService::class)->get($key, $default);
            }
        }

        return self::readAppKv($key, $default);
    }

    public static function kvSet(string $key, mixed $value): void
    {
        if (function_exists('app') && app()->bound(SheetStorageService::class)) {
            if ($key === 'payroll_overrides') {
                app(SheetStorageService::class)->setPayrollOverrides(is_array($value) ? $value : []);

                return;
            }
            if (preg_match('/^attendance_daily_(\d{4})-(\d{2})$/', $key, $matches)) {
                app(SheetStorageService::class)->setAttendanceDaily((int) $matches[2], (int) $matches[1], is_array($value) ? $value : []);

                return;
            }
            if (in_array($key, ['control_settings', 'company_profile'], true) && app()->bound(TenantSettingsService::class)) {
                app(TenantSettingsService::class)->set($key, $value);

                return;
            }
        }

        self::writeAppKv($key, $value);
    }

    public static function kvSetOn(PDO $connection, string $key, mixed $value): void
    {
        $statement = $connection->prepare('INSERT INTO app_kv (key,value,updated_at) VALUES (?,?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=excluded.updated_at');
        $statement->execute([$key, json_encode($value, JSON_UNESCAPED_UNICODE), now_iso()]);
    }

    public static function index(string $key): array
    {
        $value = self::kvGet($key, []);

        return is_array($value) ? $value : [];
    }

    public static function period(int $month, int $year): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }

    public static function idKey(string $prefix, string $id): string
    {
        return $prefix.'_'.$id;
    }

    public static function findPeriod(array $rows, int $month, int $year): ?array
    {
        $period = self::period($month, $year);
        foreach ($rows as $row) {
            if (($row['period'] ?? '') === $period) {
                return $row;
            }
        }

        return null;
    }

    public static function getSheet(string $key, string $message): array
    {
        if (preg_match('/^(attendance_sheet|payroll_sheet|pf_sheet|pf_return_sheet|esic_sheet|esic_return_sheet|ecr_sheet|fnf_sheet|gratuity_sheet|bonus_sheet|payslip)_(.+)$/', $key, $matches)
            && function_exists('app') && app()->bound(SheetStorageService::class)) {
            $sheet = app(SheetStorageService::class)->get($matches[1], $matches[2]);
            if (is_array($sheet)) {
                return $sheet;
            }
        }

        $value = self::kvGet($key, null);
        if (! is_array($value)) {
            nf($message);
        }

        return $value;
    }

    public static function deleteSheet(string $prefix, string $id): void
    {
        if (function_exists('app') && app()->bound(SheetStorageService::class)) {
            app(SheetStorageService::class)->delete($prefix, $id);

            return;
        }

        HrDatabase::tenant()->prepare('DELETE FROM app_kv WHERE key=?')->execute([self::idKey($prefix, $id)]);
        $indexKey = $prefix.'_index';
        $rows = array_values(array_filter(self::index($indexKey), fn ($row) => ((string) ($row['id'] ?? '')) !== $id));
        self::kvSet($indexKey, $rows);
    }

    public static function clearSheet(string $prefix): void
    {
        if (function_exists('app') && app()->bound(SheetStorageService::class)) {
            app(SheetStorageService::class)->clear($prefix);

            return;
        }

        $indexKey = $prefix.'_index';
        foreach (self::index($indexKey) as $row) {
            if (! empty($row['id'])) {
                HrDatabase::tenant()->prepare('DELETE FROM app_kv WHERE key=?')->execute([self::idKey($prefix, (string) $row['id'])]);
            }
        }
        HrDatabase::tenant()->prepare('DELETE FROM app_kv WHERE key=?')->execute([$indexKey]);
    }

    private static function readAppKv(string $key, mixed $default): mixed
    {
        if (! function_exists('app') || ! app()->bound(TenantManager::class)) {
            $statement = HrDatabase::tenant()->prepare('SELECT value FROM app_kv WHERE key=?');
            $statement->execute([$key]);
            $row = $statement->fetch();
            if (! $row) {
                return $default;
            }
            $decoded = json_decode((string) $row['value'], true);

            return ($decoded === null && $row['value'] !== 'null') ? $default : $decoded;
        }

        $row = app(TenantManager::class)->tenant()->table('app_kv')->where('key', $key)->first();
        if (! $row) {
            return $default;
        }
        $decoded = json_decode((string) $row->value, true);

        return ($decoded === null && $row->value !== 'null') ? $default : $decoded;
    }

    private static function writeAppKv(string $key, mixed $value): void
    {
        if (function_exists('app') && app()->bound(TenantManager::class)) {
            app(TenantManager::class)->tenant()->table('app_kv')->updateOrInsert(
                ['key' => $key],
                ['value' => json_encode($value, JSON_UNESCAPED_UNICODE), 'updated_at' => now_iso()]
            );

            return;
        }

        $statement = HrDatabase::tenant()->prepare('INSERT INTO app_kv (key,value,updated_at) VALUES (?,?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=excluded.updated_at');
        $statement->execute([$key, json_encode($value, JSON_UNESCAPED_UNICODE), now_iso()]);
    }
}
