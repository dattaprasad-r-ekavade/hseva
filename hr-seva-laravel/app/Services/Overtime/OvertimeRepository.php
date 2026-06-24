<?php

namespace App\Services\Overtime;

use App\Services\Tenant\TenantManager;

class OvertimeRepository
{
    public function __construct(private TenantManager $tenants) {}

    public function rows(array $ctx): array
    {
        $empScope = $this->employeeScope($ctx);
        $query = $this->tenants->tenant()->table('overtime_entries');

        if ($empScope !== '') {
            $query->where('emp_id', $empScope);
        }

        return $query
            ->orderByDesc('ot_date')
            ->orderByDesc('start_time')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => $this->payload((array) $r))
            ->all();
    }

    public function stats(array $rows): array
    {
        $month = gmdate('Y-m');
        $monthRows = array_values(array_filter($rows, fn ($r) => str_starts_with((string) ($r['otDate'] ?? ''), $month)));
        $sum = fn ($items, $key) => round(array_reduce($items, fn ($c, $r) => $c + f($r[$key] ?? 0), 0.0), 2);

        return [
            'entries' => count($rows),
            'totalHours' => $sum($rows, 'totalHours'),
            'totalAmount' => $sum($rows, 'amount'),
            'monthHours' => $sum($monthRows, 'totalHours'),
            'monthAmount' => $sum($monthRows, 'amount'),
        ];
    }

    public function create(array $payload): array
    {
        overtime_manage_ctx();

        $empId = up($payload['empId'] ?? '');
        if ($empId === '') {
            bad('empId is required');
        }

        $emp = null;
        foreach (employees_all() as $e) {
            if (up($e['id'] ?? '') === $empId) {
                $emp = $e;
                break;
            }
        }
        if (! $emp) {
            nf('Employee not found');
        }

        $otDate = s($payload['otDate'] ?? '');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $otDate)) {
            bad('otDate must be YYYY-MM-DD');
        }

        $startTime = s($payload['startTime'] ?? '');
        $endTime = s($payload['endTime'] ?? '');
        $hours = $this->totalHours($startTime, $endTime);
        if ($hours <= 0) {
            bad('Total hours must be greater than 0');
        }

        $rate = round(f($payload['rate'] ?? 0), 2);
        if ($rate < 0) {
            bad('rate cannot be negative');
        }

        $amount = round($hours * $rate, 2);
        $id = 'OT-'.preg_replace('/[^A-Z0-9]/', '', $empId).'-'.time();
        $now = now_iso();
        $notes = s($payload['notes'] ?? '');

        $this->tenants->tenant()->table('overtime_entries')->insert([
            'id' => $id,
            'emp_id' => $empId,
            'employee_name' => s($emp['name'] ?? $empId),
            'ot_date' => $otDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'total_hours' => $hours,
            'rate' => $rate,
            'amount' => $amount,
            'notes' => $notes,
            'created_by' => auth_actor_name(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        invalidate_salary_dependent_sheets();

        $row = $this->tenants->tenant()->table('overtime_entries')->where('id', $id)->first();

        return $row ? $this->payload((array) $row) : ['id' => $id];
    }

    public function delete(string $id): void
    {
        overtime_manage_ctx();
        $id = s($id);
        if ($id === '') {
            bad('Invalid overtime id');
        }

        $deleted = $this->tenants->tenant()->table('overtime_entries')->where('id', $id)->delete();
        if ($deleted === 0) {
            nf('Overtime entry not found');
        }

        invalidate_salary_dependent_sheets();
    }

    public function clear(): void
    {
        overtime_manage_ctx();
        $this->tenants->tenant()->table('overtime_entries')->delete();
        invalidate_salary_dependent_sheets();
    }

    public function employeeScope(array $ctx): string
    {
        $role = strtolower((string) ($ctx['role'] ?? ''));

        return $role === 'employee' ? up($ctx['empId'] ?? '') : '';
    }

    private function totalHours(string $startTime, string $endTime): float
    {
        $start = $this->timeMinutes($startTime);
        $end = $this->timeMinutes($endTime);
        if ($end <= $start) {
            $end += 24 * 60;
        }

        return round(($end - $start) / 60, 2);
    }

    private function timeMinutes(string $time): int
    {
        if (! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $m)) {
            bad('Time must be HH:MM');
        }

        return ((int) $m[1] * 60) + (int) $m[2];
    }

    private function payload(array $r): array
    {
        return [
            'id' => (string) ($r['id'] ?? ''),
            'empId' => (string) ($r['emp_id'] ?? ''),
            'employeeName' => (string) ($r['employee_name'] ?? ''),
            'otDate' => (string) ($r['ot_date'] ?? ''),
            'startTime' => (string) ($r['start_time'] ?? ''),
            'endTime' => (string) ($r['end_time'] ?? ''),
            'totalHours' => round(f($r['total_hours'] ?? 0), 2),
            'rate' => round(f($r['rate'] ?? 0), 2),
            'amount' => round(f($r['amount'] ?? 0), 2),
            'notes' => (string) ($r['notes'] ?? ''),
            'createdBy' => (string) ($r['created_by'] ?? ''),
            'createdAt' => (string) ($r['created_at'] ?? ''),
            'updatedAt' => (string) ($r['updated_at'] ?? ''),
        ];
    }
}
