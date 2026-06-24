<?php

namespace App\Services\Payroll;

use App\Services\Storage\SheetStorageService;

class PayrollGenerator
{
    public function __construct(
        private SheetStorageService $sheets,
        private StatutoryCalculator $statutory,
    ) {}

    public function generate(int $month, int $year, string $absentMode = 'LOP'): array
    {
        $clientId = req_client_id();
        $attIdx = find_period($this->sheetIndex('attendance_sheet'), $month, $year);
        if (! $attIdx) {
            bad('Attendance sheet not found for selected month. Generate Attendance Sheet first.');
        }

        $att = $this->sheet('attendance_sheet', (string) $attIdx['id']);
        $rows = $att['rows'] ?? [];
        $ctrl = control_get();
        $ov = ovr_all();
        $dim = (int) cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $out = [];
        $payrollSheetId = 'PAY-'.period($month, $year).'-'.time();
        $otherDedItems = $this->statutory->controlOtherDeductionBreakup($ctrl);
        $otherDedFixed = 0.0;
        foreach ($otherDedItems as $it) {
            $otherDedFixed += f($it['amount'] ?? 0);
        }
        $lopMap = lop_leave_days_map($month, $year);
        $otMap = overtime_monthly_map($month, $year);
        $emap = [];
        foreach (employees_active_all() as $e) {
            $emap[up($e['id'] ?? '')] = $e;
        }

        foreach ($rows as $a) {
            $eid = up($a['empId'] ?? '');
            $o = $ov[$eid] ?? [];
            $emp = $emap[$eid] ?? [];
            if (! $emp) {
                continue;
            }
            $lopFromAtt = f($a['LOP'] ?? 0);
            $lopBase = array_key_exists($eid, $lopMap) ? f($lopMap[$eid]) : $lopFromAtt;
            $lop = $lopBase + ((strtoupper($absentMode) === 'LOP') ? f($a['A'] ?? 0) : 0);
            $wo = f($a['WO'] ?? 0);
            $working = max(1.0, $dim);
            $paid = max(0.0, $working - $lop);
            $gross = (isset($o['gross']) && $o['gross'] !== null) ? f($o['gross']) : 0;
            $ctc = (isset($o['ctc']) && $o['ctc'] !== null) ? f($o['ctc']) : 0;
            $masterCtc = f($emp['baseCtc'] ?? 0);
            $base = $gross > 0 ? $gross : ($ctc > 0 ? $ctc : ($masterCtc > 0 ? $masterCtc : 25000));
            $parts = $this->statutory->splitCtc($base, $ctrl);
            $gross = f($parts['gross']);
            $lopDed = $gross * ($lop / $working);
            $earned = max(0.0, $gross - $lopDed);
            $pfAp = ($o['pfAppl'] ?? true) === true;
            $esiAp = ($o['esiAppl'] ?? true) === true;
            $ptAp = ($o['ptAppl'] ?? true) === true;
            $lwfAp = ($o['lwfAppl'] ?? true) === true;
            $stat = $this->statutory->payrollStatutoryCalc($ctrl, $gross, $earned, $pfAp, $esiAp);
            $pfW = f($stat['pfWages'] ?? 0);
            $pfEE = f($stat['pfEE'] ?? 0);
            $pfER = f($stat['pfER'] ?? 0);
            $esiEE = f($stat['esiEE'] ?? 0);
            $esiER = f($stat['esiER'] ?? 0);
            $ptEnabled = b($ctrl['ptEnabled'] ?? 'Yes');
            $pt = ($ptAp && $ptEnabled) ? f($ctrl['ptMonthly'] ?? 200) : 0;
            $lm = (int) f($ctrl['lwfMonth'] ?? 0);
            $lwf = (b($ctrl['lwfEnabled'] ?? 'Yes') && $lwfAp && ($lm === 0 || $lm === $month)) ? f($ctrl['lwfEmpAmt'] ?? 20) : 0;
            $otInfo = $otMap[$eid] ?? ['hours' => 0.0, 'amount' => 0.0, 'entries' => 0];
            $otHours = round(f($otInfo['hours'] ?? 0), 2);
            $otAmount = round(f($otInfo['amount'] ?? 0), 2);
            $incentiveAmount = incentive_total_for_period(db(), $eid, $month, $year);
            $totalEarnings = round($earned + $otAmount + $incentiveAmount, 2);
            $otherDed = $otherDedFixed;
            $dedWithoutAdvance = $pfEE + $esiEE + $pt + $lwf + $otherDed;
            $advanceDed = advance_payroll_apply(db(), $eid, $month, $year, max(0.0, $totalEarnings - $dedWithoutAdvance), $payrollSheetId);
            $advanceAmt = round(f($advanceDed['amount'] ?? 0), 2);
            $loanDed = loan_payroll_apply(db(), $eid, $month, $year, max(0.0, $totalEarnings - $dedWithoutAdvance - $advanceAmt), $payrollSheetId);
            $loanAmt = round(f($loanDed['amount'] ?? 0), 2);
            $ded = $dedWithoutAdvance + $advanceAmt + $loanAmt;
            $net = $totalEarnings - $ded;
            $workDays = max(0.0, $paid - $wo);
            $out[] = [
                'month' => period($month, $year),
                'empId' => $eid,
                'empName' => s($a['empName'] ?? $eid),
                'dept' => s($a['dept'] ?? ''),
                'desig' => s($a['desig'] ?? ''),
                'daysInMonth' => $dim,
                'paidDays' => round($paid, 2),
                'WO' => round($wo, 2),
                'workDays' => round($workDays, 2),
                'lopDays' => round($lop, 2),
                'CL' => round(f($a['CL'] ?? 0), 2),
                'SL' => round(f($a['SL'] ?? 0), 2),
                'EL' => round(f($a['EL'] ?? 0), 2),
                'gross' => round($gross, 2),
                'earnedGross' => round($earned, 2),
                'otHours' => $otHours,
                'otAmount' => $otAmount,
                'otEntries' => (int) ($otInfo['entries'] ?? 0),
                'incentiveAmount' => round($incentiveAmount, 2),
                'totalEarnings' => $totalEarnings,
                'pfWages' => round($pfW, 2),
                'pfEE' => round($pfEE, 2),
                'pfER' => round($pfER, 2),
                'esiEE' => round($esiEE, 2),
                'esiER' => round($esiER, 2),
                'pt' => round($pt, 2),
                'lwf' => round($lwf, 2),
                'otherDeductions' => round($otherDed, 2),
                'otherDeductionItems' => $otherDedItems,
                'advanceSalaryDeduction' => $advanceAmt,
                'advanceDeductionItems' => $advanceDed['items'] ?? [],
                'loanDeduction' => $loanAmt,
                'loanDeductionItems' => $loanDed['items'] ?? [],
                'totalDeductions' => round($ded, 2),
                'netPayable' => round($net, 2),
                'esiEligible' => ! empty($stat['esiApplicable']),
            ];
        }

        $totWage = round(array_sum(array_map(fn ($r) => f($r['gross'] ?? 0), $out)), 2);
        $totPfEe = round(array_sum(array_map(fn ($r) => f($r['pfEE'] ?? 0), $out)), 2);
        $totPfEr = round(array_sum(array_map(fn ($r) => f($r['pfER'] ?? 0), $out)), 2);
        $totOtAmount = round(array_sum(array_map(fn ($r) => f($r['otAmount'] ?? 0), $out)), 2);
        $totOtHours = round(array_sum(array_map(fn ($r) => f($r['otHours'] ?? 0), $out)), 2);
        $sheet = $this->sheets->save('payroll_sheet', $month, $year, $out, [
            'id' => $payrollSheetId,
            'totalPfWage' => $totWage,
            'totalPfEe' => $totPfEe,
            'totalPfEr' => $totPfEr,
            'totalOtHours' => $totOtHours,
            'totalOtAmount' => $totOtAmount,
        ]);
        mail_sheet_event('payroll_sheet', $clientId, $sheet, 'Salary Sheet', [
            'Total PF Wage' => 'Rs '.number_format($totWage, 2),
            'Total PF EE' => 'Rs '.number_format($totPfEe, 2),
            'Total PF ER' => 'Rs '.number_format($totPfEr, 2),
            'Total OT' => number_format($totOtHours, 2).' hrs / Rs '.number_format($totOtAmount, 2),
        ]);

        return $sheet;
    }

    private function sheetIndex(string $sheetType): array
    {
        return $this->sheets->index($sheetType);
    }

    private function sheet(string $sheetType, string $sheetId): array
    {
        $sheet = $this->sheets->get($sheetType, $sheetId);
        if (! is_array($sheet)) {
            nf('Attendance sheet not found');
        }

        return $sheet;
    }
}
