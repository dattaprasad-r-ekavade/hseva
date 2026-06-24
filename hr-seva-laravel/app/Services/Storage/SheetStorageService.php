<?php

namespace App\Services\Storage;

use App\Services\Tenant\TenantManager;

class SheetStorageService
{
    public function __construct(private TenantManager $tenants) {}

    public function index(string $sheetType): array
    {
        $row = $this->tenants->tenant()->table('sheet_indexes')->where('sheet_type', $sheetType)->first();
        if ($row) {
            $entries = json_decode((string) $row->entries, true);

            return is_array($entries) ? $entries : [];
        }

        return $this->legacyReadIndex($sheetType.'_index');
    }

    public function get(string $sheetType, string $sheetId): ?array
    {
        $row = $this->tenants->tenant()->table('sheets')
            ->where('sheet_type', $sheetType)
            ->where('sheet_id', $sheetId)
            ->first();
        if ($row) {
            $data = json_decode((string) $row->data, true);

            return is_array($data) ? $data : null;
        }

        return $this->legacyRead($sheetType.'_'.$sheetId);
    }

    public function save(string $sheetType, int $month, int $year, array $rows, array $extra = []): array
    {
        $period = sprintf('%04d-%02d', $year, $month);
        $id = $extra['id'] ?? ($period.'-'.time());
        $sheet = [
            'id' => $id,
            'month' => $month,
            'year' => $year,
            'period' => $period,
            'generatedAt' => gmdate('Y-m-d\TH:i:s\Z'),
            'rowCount' => count($rows),
            'rows' => $rows,
        ] + $extra;

        $meta = array_diff_key($sheet, array_flip(['rows']));
        $this->tenants->tenant()->table('sheets')->updateOrInsert(
            ['sheet_type' => $sheetType, 'sheet_id' => $id],
            [
                'month' => $month,
                'year' => $year,
                'period' => $period,
                'data' => json_encode($sheet, JSON_UNESCAPED_UNICODE),
                'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            ]
        );

        $index = $this->index($sheetType);
        array_unshift($index, [
            'id' => $id,
            'month' => $month,
            'year' => $year,
            'period' => $period,
            'generatedAt' => $sheet['generatedAt'],
            'rowCount' => count($rows),
        ] + $extra);
        $index = array_slice($index, 0, 300);
        $this->tenants->tenant()->table('sheet_indexes')->updateOrInsert(
            ['sheet_type' => $sheetType],
            ['entries' => json_encode($index, JSON_UNESCAPED_UNICODE)]
        );

        return $sheet;
    }

    public function delete(string $sheetType, string $sheetId): void
    {
        $this->tenants->tenant()->table('sheets')
            ->where('sheet_type', $sheetType)
            ->where('sheet_id', $sheetId)
            ->delete();
        $index = array_values(array_filter(
            $this->index($sheetType),
            fn ($r) => (string) ($r['id'] ?? '') !== $sheetId
        ));
        $this->tenants->tenant()->table('sheet_indexes')->updateOrInsert(
            ['sheet_type' => $sheetType],
            ['entries' => json_encode($index, JSON_UNESCAPED_UNICODE)]
        );
    }

    public function clear(string $sheetType): void
    {
        $this->tenants->tenant()->table('sheets')->where('sheet_type', $sheetType)->delete();
        $this->tenants->tenant()->table('sheet_indexes')->where('sheet_type', $sheetType)->delete();
    }

    public function attendanceDaily(int $month, int $year): array
    {
        if ($this->tableExists('attendance_daily')) {
            $row = $this->tenants->tenant()->table('attendance_daily')
                ->where('year', $year)->where('month', $month)->first();
            if ($row) {
                $map = json_decode((string) $row->records, true);

                return is_array($map) ? $map : [];
            }
        }

        return (array) $this->legacyRead(sprintf('attendance_daily_%04d-%02d', $year, $month), []);
    }

    public function setAttendanceDaily(int $month, int $year, array $map): void
    {
        if (! $this->tableExists('attendance_daily')) {
            hr_init_normalized_schema($this->tenants->tenant()->getPdo());
        }

        $this->tenants->tenant()->table('attendance_daily')->updateOrInsert(
            ['year' => $year, 'month' => $month],
            ['records' => json_encode($map, JSON_UNESCAPED_UNICODE)]
        );
    }

    public function clearAttendanceDaily(): void
    {
        $this->tenants->tenant()->table('attendance_daily')->delete();
    }

    public function payrollOverrides(): array
    {
        if (! $this->tableExists('payroll_overrides')) {
            return (array) $this->legacyRead('payroll_overrides', []);
        }

        $rows = $this->tenants->tenant()->table('payroll_overrides')->get();
        if ($rows->isNotEmpty()) {
            $out = [];
            foreach ($rows as $row) {
                $out[strtoupper((string) $row->emp_id)] = json_decode((string) $row->data, true) ?? [];
            }

            return $out;
        }

        return (array) $this->legacyRead('payroll_overrides', []);
    }

    public function setPayrollOverrides(array $overrides): void
    {
        $db = $this->tenants->tenant();
        $db->table('payroll_overrides')->delete();
        foreach ($overrides as $empId => $data) {
            $db->table('payroll_overrides')->insert([
                'emp_id' => strtoupper((string) $empId),
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    private function legacyRead(string $key, mixed $default = null): mixed
    {
        $row = $this->tenants->tenant()->table('app_kv')->where('key', $key)->first();
        if (! $row) {
            return $default;
        }
        $decoded = json_decode((string) $row->value, true);

        return ($decoded === null && $row->value !== 'null') ? $default : $decoded;
    }

    private function legacyReadIndex(string $key): array
    {
        $x = $this->legacyRead($key, []);

        return is_array($x) ? $x : [];
    }

    private function tableExists(string $table): bool
    {
        $connection = $this->tenants->tenant();
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
