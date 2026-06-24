<?php

namespace App\Services\Payslips;

use App\Services\Payroll\PayrollSheetResolver;
use App\Services\Payroll\StatutoryCalculator;
use App\Services\Sheets\SheetCrudService;
use App\Services\Storage\SheetStorageService;

class PayslipGenerator
{
    public function __construct(
        private SheetStorageService $sheets,
        private SheetCrudService $sheetCrud,
        private PayrollSheetResolver $resolver,
        private StatutoryCalculator $statutory,
    ) {}

    public function generate(int $month, int $year, string $empId, string $format = 'html'): array
    {
        $clientId = req_client_id();
        $eid = up($empId);
        if ($eid === '') {
            bad('empId is required');
        }

        $item = $this->sheetCrud->findPeriod('payroll_sheet', $month, $year);
        if (! $item) {
            nf('Payroll sheet not found for selected month. Generate salary sheet first.');
        }

        $payroll = $this->resolver->payroll($month, $year);
        $row = null;
        foreach (($payroll['rows'] ?? []) as $r) {
            if (up($r['empId'] ?? '') === $eid) {
                $row = $r;
                break;
            }
        }
        if (! $row) {
            nf('Employee not found in payroll sheet for selected month');
        }

        $ctrl = control_get();
        $emp = null;
        foreach (employees_all() as $e) {
            if ($e['id'] === $eid) {
                $emp = $e;
                break;
            }
        }
        if (! $emp) {
            nf('Employee not found');
        }

        $dim = f($row['daysInMonth'] ?? cal_days_in_month(CAL_GREGORIAN, $month, $year));
        $paid = f($row['paidDays'] ?? 0);
        $lop = f($row['lopDays'] ?? 0);
        $gross = f($row['gross'] ?? 0);
        $earned = f($row['earnedGross'] ?? 0);
        $otHours = round(f($row['otHours'] ?? 0), 2);
        $otAmount = round(f($row['otAmount'] ?? 0), 2);
        $incentiveAmount = round(f($row['incentiveAmount'] ?? 0), 2);
        $ratio = $dim > 0 ? max(0.0, min(1.0, $paid / $dim)) : 0;

        $sp = $this->statutory->splitCtc($gross, $ctrl);
        $lopDed = max(0.0, $gross - $earned);
        $adjustedGross = max(0.0, $earned);

        $earnRaw = [
            ['label' => 'Basic', 'amount' => round($sp['basic'] * $ratio, 2)],
            ['label' => 'HRA', 'amount' => round($sp['hra'] * $ratio, 2)],
            ['label' => 'Conveyance', 'amount' => round($sp['convey'] * $ratio, 2)],
            ['label' => 'DA', 'amount' => round(f($sp['da'] ?? 0) * $ratio, 2)],
            ['label' => 'Educational Allowance', 'amount' => round($sp['edu'] * $ratio, 2)],
            ['label' => 'Special Allowance', 'amount' => round($sp['special'] * $ratio, 2)],
        ];
        $earn = array_values(array_filter($earnRaw, fn ($x) => f($x['amount'] ?? 0) > 0));
        if ($otAmount > 0) {
            $earn[] = ['label' => 'Overtime ('.$otHours.' hrs)', 'amount' => $otAmount];
        }
        if ($incentiveAmount > 0) {
            $earn[] = ['label' => 'Incentive', 'amount' => $incentiveAmount];
        }

        $dedRaw = [
            ['label' => 'PF (Employee)', 'amount' => round(f($row['pfEE'] ?? 0), 2)],
            ['label' => 'ESI (Employee)', 'amount' => round(f($row['esiEE'] ?? 0), 2)],
            ['label' => 'Professional Tax', 'amount' => round(f($row['pt'] ?? 0), 2)],
            ['label' => 'LWF', 'amount' => round(f($row['lwf'] ?? 0), 2)],
        ];
        $ded = array_values(array_filter($dedRaw, fn ($x) => f($x['amount'] ?? 0) > 0));

        $advanceDeduction = round(f($row['advanceSalaryDeduction'] ?? 0), 2);
        if ($advanceDeduction > 0) {
            $ded[] = ['label' => 'Advance Salary', 'amount' => $advanceDeduction];
        }

        $loanDeduction = round(f($row['loanDeduction'] ?? 0), 2);
        if ($loanDeduction > 0) {
            $ded[] = ['label' => 'Loan EMI', 'amount' => $loanDeduction];
        }

        foreach (($row['otherDeductionItems'] ?? []) as $it) {
            if (! is_array($it)) {
                continue;
            }
            $nm = s($it['name'] ?? '');
            if ($nm === '') {
                continue;
            }
            $amt = round(f($it['amount'] ?? 0), 2);
            if ($amt <= 0) {
                continue;
            }
            $ded[] = ['label' => $nm, 'amount' => $amt];
        }

        $totE = round(array_sum(array_column($earn, 'amount')), 2);
        $totD = round(array_sum(array_column($ded, 'amount')), 2);
        $net = round(f($row['netPayable'] ?? 0), 2);
        $mk = period($month, $year);
        $id = $mk.'-'.$eid.'-'.time();

        $sheet = [
            'id' => $id,
            'month' => $month,
            'year' => $year,
            'monthKey' => $mk,
            'empId' => $eid,
            'employeeName' => $emp['name'],
            'status' => 'success',
            'format' => strtolower(trim($format)) ?: 'html',
            'generatedOn' => now_iso(),
            'data' => [
                'key' => $mk.$eid,
                'grossSalary' => round($gross, 2),
                'lopDeduction' => round($lopDed, 2),
                'adjustedGrossSalary' => round($adjustedGross, 2),
                'otHours' => $otHours,
                'otAmount' => $otAmount,
                'incentiveAmount' => $incentiveAmount,
                'company' => [
                    'name' => $ctrl['companyName'],
                    'address' => $ctrl['companyAddress'],
                    'contact' => $ctrl['companyContact'],
                    'reg' => $ctrl['companyRegNo'],
                    'pan' => $ctrl['companyPAN'],
                    'tan' => $ctrl['companyTAN'],
                    'gstin' => $ctrl['companyGSTIN'],
                ],
                'employee' => [
                    'name' => $emp['name'],
                    'uan' => $emp['uan'],
                    'designation' => $emp['desig'],
                    'pfNo' => s($emp['pfNo'] ?? '', 'PF-'.$eid),
                    'department' => $emp['dept'],
                    'esiNo' => $emp['esiNo'],
                    'doj' => $emp['doj'],
                    'bankName' => s($emp['bankName'] ?? '', ''),
                    'bankAc' => s($emp['bankAc'] ?? '', ''),
                    'payableDays' => round($paid, 2),
                    'lopDays' => round($lop, 2),
                ],
                'earnings' => $earn,
                'deductions' => $ded,
                'totals' => [
                    'earnings' => $totE,
                    'deductions' => $totD,
                    'netPay' => $net,
                ],
            ],
        ];

        $this->sheets->put('payslip', $id, $sheet, [
            'month' => $month,
            'year' => $year,
            'monthKey' => $mk,
            'empId' => $eid,
            'employeeName' => $emp['name'],
            'generatedOn' => $sheet['generatedOn'],
            'status' => 'success',
            'format' => $sheet['format'],
            'key' => $mk.$eid,
            'netPay' => $net,
        ], 1000);

        mail_payslip_event($clientId, $sheet);

        return $sheet;
    }
}
