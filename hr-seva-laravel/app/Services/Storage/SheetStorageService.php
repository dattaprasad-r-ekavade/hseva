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

        return $this->legacyIndex($sheetType.'_index');
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

        return $this->legacyGet($sheetType.'_'.$sheetId);
    }

    public function save(string $sheetType, int $month, int $year, array $rows, array $extra = []): array
    {
        $period = sprintf('%04d-%02d', $year, $month);
        $id = $period.'-'.time();
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

        $this->legacySet($sheetType.'_'.$id, $sheet);
        $this->legacySet($sheetType.'_index', $index);

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
        $this->legacyDelete($sheetType.'_'.$sheetId);
        $this->legacySet($sheetType.'_index', $index);
    }

    public function clear(string $sheetType): void
    {
        foreach ($this->index($sheetType) as $row) {
            if (! empty($row['id'])) {
                $this->legacyDelete($sheetType.'_'.$row['id']);
            }
        }
        $this->tenants->tenant()->table('sheets')->where('sheet_type', $sheetType)->delete();
        $this->tenants->tenant()->table('sheet_indexes')->where('sheet_type', $sheetType)->delete();
        $this->legacyDelete($sheetType.'_index');
    }

    public function attendanceDaily(int $month, int $year): array
    {
        $row = $this->tenants->tenant()->table('attendance_daily')
            ->where('year', $year)->where('month', $month)->first();
        if ($row) {
            $map = json_decode((string) $row->records, true);

            return is_array($map) ? $map : [];
        }

        return (array) $this->legacyGet(sprintf('attendance_daily_%04d-%02d', $year, $month), []);
    }

    public function setAttendanceDaily(int $month, int $year, array $map): void
    {
        $this->tenants->tenant()->table('attendance_daily')->updateOrInsert(
            ['year' => $year, 'month' => $month],
            ['records' => json_encode($map, JSON_UNESCAPED_UNICODE)]
        );
        $this->legacySet(sprintf('attendance_daily_%04d-%02d', $year, $month), $map);
    }

    public function payrollOverrides(): array
    {
        $rows = $this->tenants->tenant()->table('payroll_overrides')->get();
        if ($rows->isNotEmpty()) {
            $out = [];
            foreach ($rows as $row) {
                $out[strtoupper((string) $row->emp_id)] = json_decode((string) $row->data, true) ?? [];
            }

            return $out;
        }

        return (array) $this->legacyGet('payroll_overrides', []);
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
        $this->legacySet('payroll_overrides', $overrides);
    }

    private function legacyGet(string $key, mixed $default = null): mixed
    {
        $row = $this->tenants->tenant()->table('app_kv')->where('key', $key)->first();
        if (! $row) {
            return $default;
        }
        $decoded = json_decode((string) $row->value, true);

        return ($decoded === null && $row->value !== 'null') ? $default : $decoded;
    }

    private function legacySet(string $key, mixed $value): void
    {
        $this->tenants->tenant()->table('app_kv')->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($value, JSON_UNESCAPED_UNICODE), 'updated_at' => gmdate('Y-m-d\TH:i:s\Z')]
        );
    }

    private function legacyDelete(string $key): void
    {
        $this->tenants->tenant()->table('app_kv')->where('key', $key)->delete();
    }

    private function legacyIndex(string $key): array
    {
        $x = $this->legacyGet($key, []);

        return is_array($x) ? $x : [];
    }
}
