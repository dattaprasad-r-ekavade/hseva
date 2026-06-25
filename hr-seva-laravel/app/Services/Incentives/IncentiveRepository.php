<?php

namespace App\Services\Incentives;

use App\Services\Tenant\TenantManager;

class IncentiveRepository
{
    public function __construct(private TenantManager $tenants) {}

    public function all(array $query = []): array
    {
        $rows = $this->tenants->tenant()
            ->table('incentives')
            ->orderByDesc('incentive_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $empId = up($query['empId'] ?? '');
        $month = (int) ($query['month'] ?? 0);
        $year = (int) ($query['year'] ?? 0);
        $from = s($query['from'] ?? '');
        $to = s($query['to'] ?? '');
        $out = [];

        foreach ($rows as $row) {
            $norm = $this->payload((array) $row);
            if ($empId !== '' && $norm['empId'] !== $empId) {
                continue;
            }
            if ($month > 0 || $year > 0) {
                $ts = strtotime($norm['incentiveDate']);
                if ($ts === false) {
                    continue;
                }
                if ($month > 0 && (int) gmdate('n', $ts) !== $month) {
                    continue;
                }
                if ($year > 0 && (int) gmdate('Y', $ts) !== $year) {
                    continue;
                }
            }
            if ($from !== '' && $norm['incentiveDate'] < $from) {
                continue;
            }
            if ($to !== '' && $norm['incentiveDate'] > $to) {
                continue;
            }
            $out[] = $norm;
        }

        return $out;
    }

    public function find(string $id): ?array
    {
        $row = $this->tenants->tenant()->table('incentives')->where('id', $id)->first();

        return $row ? $this->payload((array) $row) : null;
    }

    public function create(array $payload): array
    {
        $empId = up($payload['empId'] ?? '');
        if ($empId === '') {
            bad('empId is required');
        }

        $dateRaw = s($payload['incentiveDate'] ?? ($payload['date'] ?? gmdate('Y-m-d')), gmdate('Y-m-d'));
        $ts = strtotime($dateRaw);
        if ($ts === false) {
            bad('Invalid incentive date');
        }
        $date = gmdate('Y-m-d', $ts);
        $amount = round(max(0.0, f($payload['amount'] ?? 0)), 2);
        if ($amount <= 0) {
            bad('amount must be greater than 0');
        }

        $remarks = s($payload['remarks'] ?? '');
        $id = 'INC-'.gmdate('YmdHis').'-'.substr(md5(uniqid((string) mt_rand(), true)), 0, 6);
        $name = $this->employeeName($empId);
        $now = now_iso();

        $this->tenants->tenant()->table('incentives')->insert([
            'id' => $id,
            'emp_id' => $empId,
            'employee_name' => $name,
            'incentive_date' => $date,
            'amount' => $amount,
            'remarks' => $remarks,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->find($id) ?? [
            'id' => $id,
            'empId' => $empId,
            'employeeName' => $name,
            'incentiveDate' => $date,
            'amount' => $amount,
            'remarks' => $remarks,
            'createdAt' => $now,
            'updatedAt' => $now,
        ];
    }

    public function delete(string $id): void
    {
        $id = s($id);
        if ($id === '') {
            bad('Invalid incentive id');
        }
        $deleted = $this->tenants->tenant()->table('incentives')->where('id', $id)->delete();
        if ($deleted === 0) {
            nf('Incentive not found');
        }
    }

    public function clear(): void
    {
        $this->tenants->tenant()->table('incentives')->delete();
    }

    private function employeeName(string $empId): string
    {
        $eid = up($empId);
        foreach (employees_all() as $emp) {
            if (up($emp['id'] ?? '') === $eid) {
                return s($emp['name'] ?? $eid, $eid);
            }
        }

        return $eid;
    }

    private function payload(array $r): array
    {
        return [
            'id' => s($r['id'] ?? ''),
            'empId' => up($r['emp_id'] ?? $r['empId'] ?? ''),
            'employeeName' => s($r['employee_name'] ?? $r['employeeName'] ?? ''),
            'incentiveDate' => s($r['incentive_date'] ?? $r['incentiveDate'] ?? $r['date'] ?? ''),
            'amount' => round(max(0.0, f($r['amount'] ?? 0)), 2),
            'remarks' => s($r['remarks'] ?? ''),
            'createdAt' => s($r['created_at'] ?? $r['createdAt'] ?? ''),
            'updatedAt' => s($r['updated_at'] ?? $r['updatedAt'] ?? ''),
        ];
    }
}
