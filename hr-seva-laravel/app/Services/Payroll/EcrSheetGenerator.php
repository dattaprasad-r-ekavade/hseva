<?php

namespace App\Services\Payroll;

use App\Services\Storage\SheetStorageService;

class EcrSheetGenerator
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
        $ctrl = control_get();

        foreach (($pf['rows'] ?? []) as $r) {
            $ecr = ecr_calc_from_pf_row($ctrl, $r);
            $rows[] = [
                'UAN' => '',
                'MEMBER_NAME' => $r['Employee_Name'],
                'GROSS_WAGES' => $ecr['gross'],
                'EPF_WAGES' => $ecr['pfWages'],
                'EPS_WAGES' => $ecr['pfWages'],
                'EDLI_WAGES' => $ecr['pfWages'],
                'EPF_CONTRI_REMITTED' => $ecr['employeePf'],
                'EPS_CONTRI_REMITTED' => $ecr['eps'],
                'EPF_EPS_DIFF_REMITTED' => $ecr['epfEpsDiff'],
                'NCP_DAYS' => 0,
                'REFUND_OF_ADVANCES' => 0,
            ];
        }

        $sheet = $this->sheets->save('ecr_sheet', $month, $year, $rows, [
            'totalGrossWages' => round(array_sum(array_column($rows, 'GROSS_WAGES')), 2),
            'totalEPFWages' => round(array_sum(array_column($rows, 'EPF_WAGES')), 2),
            'totalEPFContri' => round(array_sum(array_column($rows, 'EPF_CONTRI_REMITTED')), 2),
            'totalEPSContri' => round(array_sum(array_column($rows, 'EPS_CONTRI_REMITTED')), 2),
        ]);
        mail_sheet_event('ecr_sheet', $clientId, $sheet, 'ECR Sheet', [
            'Total EPF Contribution' => 'Rs '.number_format(f($sheet['totalEPFContri'] ?? 0), 2),
        ]);

        return $sheet;
    }
}
