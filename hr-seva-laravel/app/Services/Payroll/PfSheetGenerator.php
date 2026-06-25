<?php

namespace App\Services\Payroll;

use App\Services\Storage\SheetStorageService;

class PfSheetGenerator
{
    public function __construct(
        private SheetStorageService $sheets,
        private StatutoryCalculator $statutory,
        private PayrollSheetResolver $resolver,
    ) {}

    public function generate(int $month, int $year): array
    {
        $clientId = req_client_id();
        $payroll = $this->resolver->payroll($month, $year);
        $attendance = $this->resolver->attendance($month, $year);
        $ctrl = control_get();
        $rows = [];
        $overrides = ovr_all();
        $attMap = [];
        foreach (($attendance['rows'] ?? []) as $ar) {
            $attMap[up($ar['empId'] ?? '')] = $ar;
        }

        foreach (($payroll['rows'] ?? []) as $r) {
            $eid = up($r['empId'] ?? '');
            $o = $overrides[$eid] ?? [];
            $attRow = $attMap[$eid] ?? [];
            $gross = f($r['gross'] ?? 0);
            $earned = f($r['earnedGross'] ?? $gross);
            $pfAp = ($o['pfAppl'] ?? true) === true;
            $esiAp = ($o['esiAppl'] ?? true) === true;
            $stat = $this->statutory->payrollStatutoryCalc($ctrl, $gross, $earned, $pfAp, $esiAp);
            $w = f($stat['pfWages'] ?? 0);
            $ee = f($stat['pfEE'] ?? 0);
            $er = f($stat['pfER'] ?? 0);
            $eps = round($ee / 0.0833, 2);
            $basic = ($earned * f($ctrl['ctcBasicPct'] ?? 50)) / 100;
            $da = ($earned * f($ctrl['ctcDaPct'] ?? 30)) / 100;
            $rows[] = [
                'Month' => period($month, $year),
                'Emp_ID' => $r['empId'],
                'Employee_Name' => $r['empName'],
                'GROSS_WAGES' => round($earned, 2),
                'Basic' => round($basic, 2),
                'DA' => round($da, 2),
                'PF_Wages' => round($w, 2),
                'PF_EE' => round($ee, 2),
                'EPF_CONTRI_REMITTED' => round($ee, 2),
                'EPS_CONTRI_REMITTED' => $eps,
                'NCP_DAYS' => round(f($attRow['LOP'] ?? 0), 2),
                'PF_ER' => round($er, 2),
                'Net_Pay' => round(f($r['netPayable'] ?? 0), 2),
                'Key' => $r['empId'].'|'.period($month, $year),
            ];
        }

        $sheet = $this->sheets->save('pf_sheet', $month, $year, $rows, [
            'totalWage' => round(array_sum(array_column($rows, 'PF_Wages')), 2),
            'totalEE' => round(array_sum(array_column($rows, 'PF_EE')), 2),
            'totalER' => round(array_sum(array_column($rows, 'PF_ER')), 2),
        ]);
        mail_sheet_event('pf_sheet', $clientId, $sheet, 'PF Sheet', [
            'Total Wage' => 'Rs '.number_format(f($sheet['totalWage'] ?? 0), 2),
        ]);

        return $sheet;
    }
}
