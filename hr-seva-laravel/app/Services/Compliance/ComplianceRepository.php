<?php

namespace App\Services\Compliance;

use App\Services\Sheets\SheetCrudService;
use App\Services\Storage\TenantSettingsService;
use App\Services\Tenant\TenantManager;

class ComplianceRepository
{
    private const CHALLAN_INDEX_KEY = 'compliance_challan_index';

    public function __construct(
        private TenantSettingsService $settings,
        private SheetCrudService $sheets,
        private TenantManager $tenants,
    ) {}

    public function tasks(int $month, int $year): array
    {
        $stored = $this->settings->get('compliance_'.period($month, $year));

        return is_array($stored) ? $stored : $this->defaultTasks($month, $year);
    }

    public function saveTasks(int $month, int $year, array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'dueDate' => s($r['dueDate'] ?? ''),
                'task' => s($r['task'] ?? ''),
                'status' => s($r['status'] ?? 'Pending', 'Pending'),
                'action' => s($r['action'] ?? 'View', 'View'),
                'notes' => s($r['notes'] ?? ''),
            ];
        }
        $this->settings->set('compliance_'.period($month, $year), $out);

        return $out;
    }

    public function resetTasks(int $month, int $year): array
    {
        $defaults = $this->defaultTasks($month, $year);
        $this->settings->set('compliance_'.period($month, $year), $defaults);

        return $defaults;
    }

    public function clearTasks(): void
    {
        $this->tenants->tenant()
            ->table('app_kv')
            ->where('key', 'like', 'compliance_%')
            ->delete();
    }

    public function challans(): array
    {
        $rows = $this->settings->get(self::CHALLAN_INDEX_KEY, []);

        return is_array($rows) ? $rows : [];
    }

    public function upsertChallan(array $raw): array
    {
        $n = $this->normalizeChallan($raw);
        $rows = $this->challans();
        $id = s($n['id'] ?? '');

        if ($id === '') {
            $id = period($n['month'], $n['year']).'-'.time().'-'.substr(bin2hex(random_bytes(3)), 0, 6);
        }

        $existing = null;
        foreach ($rows as $r) {
            if ((string) ($r['id'] ?? '') === $id) {
                $existing = $r;
                break;
            }
        }

        $row = $n;
        $row['id'] = $id;
        $row['createdAt'] = (string) ($existing['createdAt'] ?? $row['createdAt'] ?? now_iso());
        $row['updatedAt'] = now_iso();

        $next = [];
        $updated = false;
        foreach ($rows as $r) {
            if ((string) ($r['id'] ?? '') === $id) {
                $next[] = $row;
                $updated = true;

                continue;
            }
            $next[] = $r;
        }
        if (! $updated) {
            array_unshift($next, $row);
        }

        usort($next, fn ($a, $b) => strcmp((string) ($b['updatedAt'] ?? ''), (string) ($a['updatedAt'] ?? '')));
        $this->settings->set(self::CHALLAN_INDEX_KEY, array_slice(array_values($next), 0, 800));
        mail_challan_event('compliance_challan', req_client_id(), $row, 'Compliance Challan');

        return $row;
    }

    public function deleteChallan(string $id): void
    {
        $rows = $this->challans();
        $next = array_values(array_filter($rows, fn ($r) => (string) ($r['id'] ?? '') !== $id));
        if (count($next) === count($rows)) {
            nf('Compliance challan not found');
        }
        $this->settings->set(self::CHALLAN_INDEX_KEY, $next);
    }

    public function clearChallans(): void
    {
        $this->settings->set(self::CHALLAN_INDEX_KEY, []);
    }

    private function defaultTasks(int $month, int $year): array
    {
        $ld = (int) cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $pf = $this->sheets->findPeriod('pf_return_sheet', $month, $year) !== null;
        $es = $this->sheets->findPeriod('esic_return_sheet', $month, $year) !== null;
        $py = $this->sheets->findPeriod('payroll_sheet', $month, $year) !== null;

        return [
            [
                'dueDate' => sprintf('%04d-%02d-%02d', $year, $month, min(15, $ld)),
                'task' => 'ESI Return / Payment (Monthly)',
                'status' => $es ? 'Completed' : 'Pending',
                'action' => 'View',
                'notes' => '',
            ],
            [
                'dueDate' => sprintf('%04d-%02d-%02d', $year, $month, min(15, $ld)),
                'task' => 'PF ECR Preparation (Monthly)',
                'status' => $pf ? 'Completed' : ($py ? 'In Progress' : 'Pending'),
                'action' => 'View',
                'notes' => '',
            ],
            [
                'dueDate' => sprintf('%04d-%02d-%02d', $year, $month, min(20, $ld)),
                'task' => 'Professional Tax (if applicable)',
                'status' => $py ? 'In Progress' : 'Pending',
                'action' => 'View',
                'notes' => '',
            ],
            [
                'dueDate' => sprintf('%04d-%02d-%02d', $year, $month, $ld),
                'task' => 'LWF Deduction Review (if applicable)',
                'status' => $py ? 'Completed' : 'Pending',
                'action' => 'View',
                'notes' => '',
            ],
        ];
    }

    private function normalizeChallan(array $raw): array
    {
        $month = (int) ($raw['month'] ?? 0);
        $year = (int) ($raw['year'] ?? 0);
        if ($month < 1 || $month > 12 || $year < 2000) {
            bad('month/year required');
        }

        $type = s($raw['type'] ?? '');
        $dueDate = s($raw['dueDate'] ?? '');
        if ($type === '' || $dueDate === '') {
            bad('type and dueDate are required');
        }

        $status = s($raw['status'] ?? 'Pending', 'Pending');
        if (! in_array($status, ['Pending', 'In Progress', 'Completed'], true)) {
            $status = 'Pending';
        }

        $amount = round(f($raw['amount'] ?? 0), 2);
        $notes = s($raw['notes'] ?? '');
        $pdfDataUrl = s($raw['pdfDataUrl'] ?? '');
        if ($pdfDataUrl !== '' && stripos($pdfDataUrl, 'data:application/pdf') !== 0) {
            bad('Valid PDF data is required');
        }

        $createdAt = s($raw['createdAt'] ?? '', '');
        $updatedAt = s($raw['updatedAt'] ?? '', '');
        if ($createdAt === '') {
            $createdAt = now_iso();
        }
        if ($updatedAt === '') {
            $updatedAt = now_iso();
        }

        return [
            'id' => s($raw['id'] ?? ''),
            'month' => $month,
            'year' => $year,
            'period' => period($month, $year),
            'type' => $type,
            'dueDate' => $dueDate,
            'status' => $status,
            'amount' => $amount,
            'notes' => $notes,
            'pdfDataUrl' => $pdfDataUrl,
            'createdAt' => $createdAt,
            'updatedAt' => $updatedAt,
        ];
    }
}
