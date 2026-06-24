<?php

namespace App\Services\Payroll;

use App\Services\Storage\SheetStorageService;

class EsicSheetGenerator
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
        $ctrl = control_get();
        $rows = [];
        $emap = [];
        foreach (employees_active_all() as $e) {
            $emap[up($e['id'] ?? '')] = $e;
        }

        $fnfExitByEmp = [];
        foreach ($this->sheets->index('fnf_sheet') as $fx) {
            $empId = up($fx['empId'] ?? '');
            if ($empId === '' || isset($fnfExitByEmp[$empId])) {
                continue;
            }
            $fid = s($fx['id'] ?? '');
            if ($fid === '') {
                continue;
            }
            $sheet = $this->sheets->get('fnf_sheet', $fid);
            if (! is_array($sheet)) {
                continue;
            }
            $exitDate = s($sheet['exitDate'] ?? '');
            if ($exitDate !== '') {
                $fnfExitByEmp[$empId] = $exitDate;
            }
        }

        foreach (($payroll['rows'] ?? []) as $r) {
            $eid = up($r['empId'] ?? '');
            $emp = $emap[$eid] ?? [];
            if (! $emp) {
                continue;
            }
            $w = f($r['earnedGross'] ?? 0);
            $esiEligible = b($r['esiEligible'] ?? false);
            $esiApplicable = $w > 0 && $esiEligible && f($r['esiEE'] ?? 0) > 0;
            if (! $esiApplicable) {
                continue;
            }
            $gross = f($r['gross'] ?? 0);
            $stat = $this->statutory->payrollStatutoryCalc($ctrl, $gross, $w, true, true);
            $ee = f($stat['esiEE'] ?? 0);
            $er = f($stat['esiER'] ?? 0);
            $ncp = f($r['lopDays'] ?? 0);
            $ipNoRaw = s($emp['esiNo'] ?? '');
            $ipNo = preg_replace('/\D+/', '', $ipNoRaw) ?? '';
            $ipNo = substr($ipNo, 0, 10);
            $ipName = preg_replace('/[^A-Za-z ]+/', '', s($r['empName'] ?? '')) ?: s($r['empName'] ?? '');
            $paidDays = f($r['paidDays'] ?? 0);
            $reasonCode = 0;
            $lastWorkingDay = '';
            $fnfExit = s($fnfExitByEmp[$eid] ?? '');
            if ($fnfExit !== '') {
                $ts = strtotime($fnfExit);
                if ($ts !== false) {
                    $lastWorkingDay = date('d/m/Y', $ts);
                } elseif (preg_match('/^\d{2}[-\/]\d{2}[-\/]\d{4}$/', $fnfExit)) {
                    $lastWorkingDay = $fnfExit;
                }
            }
            $rows[] = [
                'Sr No' => count($rows) + 1,
                'Month' => period($month, $year),
                'IP Number' => $ipNo,
                'IP Name' => $ipName,
                'No of Days for which wages paid/payable during the month' => round($paidDays, 2),
                'Total Monthly Wages' => round($w, 2),
                'Reason Code for Zero workings days' => (int) $reasonCode,
                'Last Working Day' => $lastWorkingDay,
                'Emp_ID' => $r['empId'],
                'Employee_Name' => $r['empName'],
                'ESI_No' => $ipNo,
                'IP_No' => $ipNo,
                'IP_Name' => $ipName,
                'No_of_Days_Paid' => round($paidDays, 2),
                'Total_Monthly_Wages' => round($w, 2),
                'Reason_Code_Zero_Working_Days' => (int) $reasonCode,
                'Last_Working_Day' => $lastWorkingDay,
                'Gross_Wages' => round($gross, 2),
                'ESI_Wages' => round($w, 2),
                'ESI_EE' => round($ee, 2),
                'ESI_ER' => round($er, 2),
                'EE_Contribution' => round($ee, 2),
                'ER_Contribution' => round($er, 2),
                'Total_ESI' => round($ee + $er, 2),
                'NCP_Days' => round($ncp, 2),
            ];
        }

        $sheet = $this->sheets->save('esic_sheet', $month, $year, $rows, [
            'totalWage' => round(array_sum(array_column($rows, 'ESI_Wages')), 2),
            'totalEE' => round(array_sum(array_column($rows, 'ESI_EE')), 2),
            'totalER' => round(array_sum(array_column($rows, 'ESI_ER')), 2),
            'totalESI' => round(array_sum(array_column($rows, 'Total_ESI')), 2),
        ]);
        mail_sheet_event('esic_sheet', $clientId, $sheet, 'ESIC Sheet', [
            'Total ESI' => 'Rs '.number_format(f($sheet['totalESI'] ?? 0), 2),
        ]);

        return $sheet;
    }
}
