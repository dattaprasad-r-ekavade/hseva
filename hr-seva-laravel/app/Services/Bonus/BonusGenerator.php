<?php

namespace App\Services\Bonus;

use App\Services\Storage\SheetStorageService;

class BonusGenerator
{
    public function __construct(private SheetStorageService $sheets) {}

    public function generatePreview(int $month, int $year): array
    {
        if ($month < 1 || $month > 12 || $year < 2000) {
            bad('month and year are required');
        }

        $ctrl = $this->controlDefaults(control_get());
        if (! $ctrl['enabled']) {
            bad('Bonus module is disabled in control page');
        }

        $rows = [];
        foreach (employees_active_all() as $emp) {
            $rows[] = [
                'empId' => (string) ($emp['id'] ?? ''),
                'employeeName' => (string) ($emp['name'] ?? ''),
                'dept' => (string) ($emp['dept'] ?? ''),
                'desig' => (string) ($emp['desig'] ?? ''),
                'minimumWage' => $ctrl['minimumWage'],
                'multiplierMonths' => $ctrl['multiplierMonths'],
                'bonusPct' => $ctrl['bonusPercent'],
                'bonusAmount' => $this->calcAmount($ctrl['minimumWage'], $ctrl['multiplierMonths'], $ctrl['bonusPercent']),
            ];
        }

        return [
            'month' => $month,
            'year' => $year,
            'period' => period($month, $year),
            'defaults' => $ctrl,
            'rows' => $rows,
        ];
    }

    public function saveSheet(array $payload): array
    {
        $clientId = req_client_id();
        $month = (int) ($payload['month'] ?? 0);
        $year = (int) ($payload['year'] ?? 0);
        if ($month < 1 || $month > 12 || $year < 2000) {
            bad('month and year are required');
        }

        $rows = $this->normalizeRows(is_array($payload['rows'] ?? null) ? $payload['rows'] : []);
        $total = round(array_sum(array_map(fn ($r) => f($r['bonusAmount'] ?? 0), $rows)), 2);
        $sheet = $this->sheets->save('bonus_sheet', $month, $year, $rows, [
            'rowCount' => count($rows),
            'totalBonus' => $total,
        ]);
        mail_sheet_event('bonus_sheet', $clientId, $sheet, 'Bonus Sheet', [
            'Total Bonus' => 'Rs '.number_format($total, 2),
        ]);

        return $sheet;
    }

    private function controlDefaults(array $ctrl): array
    {
        $enabled = strtolower((string) ($ctrl['bonusEnabled'] ?? 'Yes')) !== 'no';
        $minimumWage = round(max(0.0, f($ctrl['bonusMinimumWage'] ?? 0)), 2);
        $months = round(max(0.0, f($ctrl['bonusMultiplierMonths'] ?? 12)), 2);
        $percent = round(max(0.0, f($ctrl['bonusPercent'] ?? 0)), 2);

        return [
            'enabled' => $enabled,
            'minimumWage' => $minimumWage,
            'multiplierMonths' => $months,
            'bonusPercent' => $percent,
        ];
    }

    private function calcAmount(float $minimumWage, float $months, float $bonusPct): float
    {
        return round(max(0.0, $minimumWage) * max(0.0, $months) * max(0.0, $bonusPct) / 100.0, 2);
    }

    private function normalizeRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $mw = round(max(0.0, f($row['minimumWage'] ?? 0)), 2);
            $months = round(max(0.0, f($row['multiplierMonths'] ?? 0)), 2);
            $pct = round(max(0.0, f($row['bonusPct'] ?? 0)), 2);
            $out[] = [
                'empId' => up($row['empId'] ?? ''),
                'employeeName' => s($row['employeeName'] ?? ''),
                'dept' => s($row['dept'] ?? ''),
                'desig' => s($row['desig'] ?? ''),
                'minimumWage' => $mw,
                'multiplierMonths' => $months,
                'bonusPct' => $pct,
                'bonusAmount' => $this->calcAmount($mw, $months, $pct),
            ];
        }

        return $out;
    }
}
