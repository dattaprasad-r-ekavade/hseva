<?php

namespace App\Services\Payroll;

use App\Services\Storage\SheetStorageService;

class EsicReturnGenerator
{
    public function __construct(
        private SheetStorageService $sheets,
        private EsicSheetGenerator $esicSheets,
    ) {}

    public function generate(int $month, int $year): array
    {
        $clientId = req_client_id();
        $item = find_period($this->sheets->index('esic_sheet'), $month, $year);
        $esic = $item
            ? ($this->sheets->get('esic_sheet', (string) $item['id']) ?? $this->esicSheets->generate($month, $year))
            : $this->esicSheets->generate($month, $year);
        $rows = $esic['rows'] ?? [];

        $sheet = $this->sheets->save('esic_return_sheet', $month, $year, $rows, [
            'totalWage' => round(array_sum(array_column($rows, 'ESI_Wages')), 2),
            'totalEE' => round(array_sum(array_column($rows, 'ESI_EE')), 2),
            'totalER' => round(array_sum(array_column($rows, 'ESI_ER')), 2),
            'totalESI' => round(array_sum(array_column($rows, 'Total_ESI')), 2),
        ]);
        mail_sheet_event('esic_return_sheet', $clientId, $sheet, 'ESIC Return', [
            'Total ESI' => 'Rs '.number_format(f($sheet['totalESI'] ?? 0), 2),
        ]);

        return $sheet;
    }
}
