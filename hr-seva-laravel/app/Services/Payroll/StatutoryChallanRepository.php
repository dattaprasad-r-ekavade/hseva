<?php

namespace App\Services\Payroll;

use App\Services\Storage\TenantSettingsService;

class StatutoryChallanRepository
{
    public function __construct(private TenantSettingsService $settings) {}

    public function pfList(): array
    {
        return $this->list('pf_return_challan_index');
    }

    public function pfCreate(array $raw): array
    {
        return $this->create('pf_return_challan_index', 'pf_challan', $raw, 'PF Challan');
    }

    public function pfDelete(string $id): void
    {
        $this->delete('pf_return_challan_index', $id, 'PF challan not found');
    }

    public function pfClear(): void
    {
        $this->settings->set('pf_return_challan_index', []);
    }

    public function esicList(): array
    {
        return $this->list('esic_return_challan_index');
    }

    public function esicCreate(array $raw): array
    {
        return $this->create('esic_return_challan_index', 'esic_challan', $raw, 'ESIC Challan');
    }

    public function esicDelete(string $id): void
    {
        $this->delete('esic_return_challan_index', $id, 'ESIC challan not found');
    }

    public function esicClear(): void
    {
        $this->settings->set('esic_return_challan_index', []);
    }

    private function list(string $key): array
    {
        $rows = $this->settings->get($key, []);

        return is_array($rows) ? $rows : [];
    }

    private function saveAll(string $key, array $rows): void
    {
        $this->settings->set($key, array_slice(array_values($rows), 0, 500));
    }

    private function normalize(array $raw): array
    {
        $month = (int) ($raw['month'] ?? 0);
        $year = (int) ($raw['year'] ?? 0);
        if ($month < 1 || $month > 12 || $year < 2000) {
            bad('month/year required');
        }

        $challanNo = s($raw['challanNo'] ?? '');
        $paidDate = s($raw['paidDate'] ?? '');
        $pdfDataUrl = s($raw['pdfDataUrl'] ?? '');
        $amount = f($raw['amount'] ?? 0);
        if ($challanNo === '' || $paidDate === '' || $amount <= 0) {
            bad('challanNo, paidDate and amount are required');
        }
        if ($pdfDataUrl === '' || stripos($pdfDataUrl, 'data:application/pdf') !== 0) {
            bad('Valid PDF data is required');
        }

        return [
            'id' => s($raw['id'] ?? ''),
            'month' => $month,
            'year' => $year,
            'period' => period($month, $year),
            'challanNo' => $challanNo,
            'paidDate' => $paidDate,
            'amount' => round($amount, 2),
            'pdfDataUrl' => $pdfDataUrl,
            'createdOn' => s($raw['createdOn'] ?? now_iso(), now_iso()),
        ];
    }

    private function create(string $key, string $mailBase, array $raw, string $label): array
    {
        $n = $this->normalize($raw);
        $id = period($n['month'], $n['year']).'-'.time().'-'.substr(bin2hex(random_bytes(3)), 0, 6);
        $row = $n;
        $row['id'] = $id;
        $rows = $this->list($key);
        array_unshift($rows, $row);
        $this->saveAll($key, $rows);
        mail_challan_event($mailBase, req_client_id(), $row, $label);

        return $row;
    }

    private function delete(string $key, string $id, string $notFound): void
    {
        $rows = $this->list($key);
        $next = array_values(array_filter($rows, fn ($r) => (string) ($r['id'] ?? '') !== $id));
        if (count($next) === count($rows)) {
            nf($notFound);
        }
        $this->saveAll($key, $next);
    }
}
