<?php

namespace App\Services\Payroll;

use App\Services\Storage\SheetStorageService;

class PfReturnGenerator
{
    public function __construct(
        private SheetStorageService $sheets,
        private PfSheetGenerator $pfSheets,
    ) {}

    public function generate(int $month, int $year): array
    {
        $clientId = req_client_id();
        $item = find_period($this->sheets->index('pf_sheet'), $month, $year);
        $pf = $item
            ? ($this->sheets->get('pf_sheet', (string) $item['id']) ?? $this->pfSheets->generate($month, $year))
            : $this->pfSheets->generate($month, $year);
        $rows = [];
        $emap = [];
        foreach (employees_active_all() as $e) {
            $emap[up($e['id'] ?? '')] = $e;
        }

        foreach (($pf['rows'] ?? []) as $r) {
            $eid = up($r['Emp_ID'] ?? '');
            $emp = $emap[$eid] ?? [];
            if (! $emp) {
                continue;
            }
            $rows[] = [
                'Month' => $r['Month'],
                'Emp_ID' => $r['Emp_ID'],
                'Employee_Name' => $r['Employee_Name'],
                'UAN' => s($emp['uan'] ?? ''),
                'PF_No' => 'PF-'.$eid,
                'PF_Wages' => $r['PF_Wages'],
                'PF_EE' => $r['PF_EE'],
                'PF_ER' => $r['PF_ER'],
                'Total_PF' => round(f($r['PF_EE']) + f($r['PF_ER']), 2),
            ];
        }

        $sheet = $this->sheets->save('pf_return_sheet', $month, $year, $rows, [
            'totalWage' => round(array_sum(array_column($rows, 'PF_Wages')), 2),
            'totalEE' => round(array_sum(array_column($rows, 'PF_EE')), 2),
            'totalER' => round(array_sum(array_column($rows, 'PF_ER')), 2),
            'totalPF' => round(array_sum(array_column($rows, 'Total_PF')), 2),
        ]);
        mail_sheet_event('pf_return_sheet', $clientId, $sheet, 'PF Return', [
            'Total PF' => 'Rs '.number_format(f($sheet['totalPF'] ?? 0), 2),
        ]);

        return $sheet;
    }
}
