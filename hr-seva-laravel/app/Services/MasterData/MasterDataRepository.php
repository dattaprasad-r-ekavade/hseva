<?php

namespace App\Services\MasterData;

use App\Services\Tenant\TenantManager;

class MasterDataRepository
{
    public function __construct(private TenantManager $tenants) {}

    public function attendanceStatuses(bool $activeOnly = false): array
    {
        $rows = $this->tenants->central()
            ->table('attendance_status_master')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn ($r) => $this->attendanceStatusPayload((array) $r))
            ->all();

        if ($activeOnly) {
            $rows = array_values(array_filter($rows, fn ($r) => ! empty($r['isActive'])));
        }

        return $rows;
    }

    public function upsertAttendanceStatus(array $payload, bool $isUpdate): array
    {
        $n = $this->normalizeAttendanceStatus($payload);
        $ts = now_iso();
        $db = $this->tenants->central();

        if ($isUpdate) {
            $db->table('attendance_status_master')
                ->where('code', $n['code'])
                ->update([
                    'short_label' => $n['shortLabel'],
                    'full_label' => $n['fullLabel'],
                    'button_class' => $n['buttonClass'],
                    'sort_order' => $n['sortOrder'],
                    'is_active' => $n['isActive'],
                    'note_required' => $n['noteRequired'],
                    'is_paid' => $n['isPaid'],
                    'updated_at' => $ts,
                ]);
        } else {
            $db->table('attendance_status_master')->insert([
                'code' => $n['code'],
                'short_label' => $n['shortLabel'],
                'full_label' => $n['fullLabel'],
                'button_class' => $n['buttonClass'],
                'sort_order' => $n['sortOrder'],
                'is_active' => $n['isActive'],
                'note_required' => $n['noteRequired'],
                'is_paid' => $n['isPaid'],
                'created_at' => $ts,
                'updated_at' => $ts,
            ]);
        }

        $row = $db->table('attendance_status_master')->where('code', $n['code'])->first();

        return $this->attendanceStatusPayload($row ? (array) $row : []);
    }

    public function deleteAttendanceStatus(string $code): void
    {
        $code = up($code);
        if ($code === '') {
            bad('Code is required');
        }
        $this->tenants->central()->table('attendance_status_master')->where('code', $code)->delete();
    }

    public function employeeTypes(bool $activeOnly = false): array
    {
        $query = $this->tenants->tenant()
            ->table('employee_type_master')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->orderBy('code');

        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        return $query->get()
            ->map(fn ($r) => $this->employeeTypePayload((array) $r))
            ->all();
    }

    public function upsertEmployeeType(array $payload, bool $isUpdate): array
    {
        $n = $this->normalizeEmployeeType($payload);
        $db = $this->tenants->tenant();
        $ts = now_iso();

        if ($isUpdate) {
            $existing = $db->table('employee_type_master')->where('code', $n['code'])->first();
            if (! $existing) {
                nf('Employee type not found');
            }
            $db->table('employee_type_master')->where('code', $n['code'])->update([
                'label' => $n['label'],
                'sort_order' => $n['sortOrder'],
                'is_active' => $n['isActive'] ? 1 : 0,
                'updated_at' => $ts,
            ]);
        } else {
            $db->table('employee_type_master')->insert([
                'code' => $n['code'],
                'label' => $n['label'],
                'sort_order' => $n['sortOrder'],
                'is_active' => $n['isActive'] ? 1 : 0,
                'created_at' => $ts,
                'updated_at' => $ts,
            ]);
        }

        $row = $db->table('employee_type_master')->where('code', $n['code'])->first();

        return $row
            ? $this->employeeTypePayload((array) $row)
            : ['code' => $n['code'], 'label' => $n['label'], 'sortOrder' => $n['sortOrder'], 'isActive' => $n['isActive'], '__updatedAt' => $ts];
    }

    public function deleteEmployeeType(string $code): void
    {
        $code = up($code);
        if ($code === '') {
            bad('Employee type code is required');
        }

        $label = $this->tenants->tenant()->table('employee_type_master')->where('code', $code)->value('label');
        if ($label !== null) {
            $inUse = $this->tenants->tenant()->table('employees')->where('type', $label)->count();
            if ($inUse > 0) {
                j(['detail' => 'Employee type is in use by existing employees'], 409);
            }
        }

        $this->tenants->tenant()->table('employee_type_master')->where('code', $code)->delete();
    }

    private function normalizeAttendanceStatus(array $raw): array
    {
        $code = up($raw['code'] ?? '');
        if ($code === '') {
            bad('Code is required');
        }
        if (! preg_match('/^[A-Z][A-Z0-9_\\-]{0,11}$/', $code)) {
            bad('Code must start with a letter and use only A-Z, 0-9, dash or underscore (max 12 chars)');
        }

        $short = strtoupper(trim((string) ($raw['shortLabel'] ?? $raw['short_label'] ?? $code)));
        $full = trim((string) ($raw['fullLabel'] ?? $raw['full_label'] ?? ''));
        if ($short === '') {
            $short = $code;
        }
        if ($full === '') {
            bad('Full label is required');
        }

        $button = trim((string) ($raw['buttonClass'] ?? $raw['button_class'] ?? 'btn-outline-secondary'));
        if ($button === '') {
            $button = 'btn-outline-secondary';
        }

        $sort = (int) ($raw['sortOrder'] ?? $raw['sort_order'] ?? 0);
        $active = ! array_key_exists('isActive', $raw) && ! array_key_exists('is_active', $raw)
            ? 1 : (b($raw['isActive'] ?? $raw['is_active'] ?? true) ? 1 : 0);
        $note = ! array_key_exists('noteRequired', $raw) && ! array_key_exists('note_required', $raw)
            ? 0 : (b($raw['noteRequired'] ?? $raw['note_required'] ?? false) ? 1 : 0);
        $paid = ! array_key_exists('isPaid', $raw) && ! array_key_exists('is_paid', $raw)
            ? 1 : (b($raw['isPaid'] ?? $raw['is_paid'] ?? true) ? 1 : 0);

        return [
            'code' => $code,
            'shortLabel' => $short,
            'fullLabel' => $full,
            'buttonClass' => $button,
            'sortOrder' => $sort,
            'isActive' => $active,
            'noteRequired' => $note,
            'isPaid' => $paid,
        ];
    }

    private function normalizeEmployeeType(array $raw): array
    {
        $code = up(preg_replace('/[^A-Z0-9_-]+/', '_', (string) ($raw['code'] ?? '')) ?? '');
        $code = trim($code, '_');
        $label = s($raw['label'] ?? '');
        if ($code === '') {
            bad('Employee type code is required');
        }
        if ($label === '') {
            bad('Employee type label is required');
        }
        if (strlen($code) > 24) {
            bad('Employee type code must be 24 characters or fewer');
        }

        return [
            'code' => $code,
            'label' => $label,
            'sortOrder' => max(0, (int) ($raw['sortOrder'] ?? 0)),
            'isActive' => b($raw['isActive'] ?? true),
        ];
    }

    private function attendanceStatusPayload(array $r): array
    {
        return [
            'code' => (string) ($r['code'] ?? ''),
            'shortLabel' => (string) ($r['short_label'] ?? ''),
            'fullLabel' => (string) ($r['full_label'] ?? ''),
            'buttonClass' => (string) ($r['button_class'] ?? 'btn-outline-secondary'),
            'sortOrder' => (int) ($r['sort_order'] ?? 0),
            'isActive' => ((int) ($r['is_active'] ?? 0)) === 1,
            'noteRequired' => ((int) ($r['note_required'] ?? 0)) === 1,
            'isPaid' => ((int) ($r['is_paid'] ?? 0)) === 1,
        ];
    }

    private function employeeTypePayload(array $r): array
    {
        return [
            'code' => (string) ($r['code'] ?? ''),
            'label' => (string) ($r['label'] ?? ''),
            'sortOrder' => (int) ($r['sort_order'] ?? 0),
            'isActive' => ((int) ($r['is_active'] ?? 1)) === 1,
            '__updatedAt' => (string) ($r['updated_at'] ?? ''),
        ];
    }
}
