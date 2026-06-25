<?php

namespace App\Services\Fnf;

use App\Services\Payroll\StatutoryCalculator;
use App\Services\Storage\SheetStorageService;

class FnfGenerator
{
    public function __construct(
        private SheetStorageService $sheets,
        private StatutoryCalculator $statutory,
    ) {}

    public function generate(array $payload): array
    {
        $clientId = req_client_id();
        $eid = up($payload['empId'] ?? '');
        $exit = s($payload['exitDate'] ?? '');
        if ($eid === '' || $exit === '') {
            bad('empId and exitDate are required');
        }

        $resignation = s($payload['resignationDate'] ?? ($payload['resignation_date'] ?? $exit));
        $gross = f($payload['gross'] ?? 0);
        $paid = f($payload['paidDays'] ?? 0);
        $lop = f($payload['lopDays'] ?? 0);
        $el = f($payload['elDays'] ?? 0);
        $bonus = f($payload['bonus'] ?? 0);
        $adv = f($payload['advance'] ?? 0);
        $notice = f($payload['notice'] ?? 0);
        if ($gross <= 0) {
            bad('Gross salary must be greater than 0');
        }

        $exitTs = strtotime($exit);
        $monthDays = 30;
        if ($exitTs !== false) {
            $monthDays = (int) cal_days_in_month(CAL_GREGORIAN, (int) gmdate('n', $exitTs), (int) gmdate('Y', $exitTs));
        }

        $attCalc = fnf_paid_lop_till_exit($eid, $exit);
        if (is_array($attCalc)) {
            $paid = f($attCalc['paidDays'] ?? $paid);
            $lop = f($attCalc['lopDays'] ?? $lop);
            $monthDays = (int) f($attCalc['monthDays'] ?? $monthDays);
        }
        if ($monthDays <= 0) {
            $monthDays = 30;
        }

        $ctrl = control_get();
        $otherItems = $this->statutory->controlOtherDeductionBreakup($ctrl);
        $otherTotal = 0.0;
        foreach ($otherItems as $it) {
            $otherTotal += f($it['amount'] ?? 0);
        }

        $pd = $gross > 0 ? $gross / (float) $monthDays : 0.0;
        $earned = $pd * $paid;
        $lopDed = $pd * $lop;
        $enc = $pd * $el;

        $name = $eid;
        $emp = [];
        foreach (employees_all() as $e) {
            if ($e['id'] === $eid) {
                $name = $e['name'];
                $emp = $e;
                break;
            }
        }

        $gratuityInfo = fnf_gratuity_fetch($eid, $exit);
        $gratuityAmount = f($gratuityInfo['amount'] ?? 0);
        $incentiveAmount = incentive_total_till_date_for_month(db(), $eid, $exit);
        if ($bonus <= 0) {
            $bonus = $incentiveAmount;
        }

        $advanceOutstanding = advance_outstanding_for_employee(db(), $eid, $exit);
        $loanOutstanding = loan_outstanding_for_employee(db(), $eid, $exit);
        if ($adv <= 0) {
            $adv = f($advanceOutstanding['amount'] ?? 0) + f($loanOutstanding['amount'] ?? 0);
        }

        $pfAp = strtolower((string) ($emp['pf'] ?? 'yes')) !== 'no';
        $esiAp = strtolower((string) ($emp['esi'] ?? 'yes')) !== 'no';
        $ptAp = true;
        $lwfAp = true;
        $stat = $this->statutory->payrollStatutoryCalc($ctrl, $gross, $earned, $pfAp, $esiAp);
        $pfEE = f($stat['pfEE'] ?? 0);
        $esiEE = f($stat['esiEE'] ?? 0);
        $ptEnabled = b($ctrl['ptEnabled'] ?? 'Yes');
        $pt = ($ptAp && $ptEnabled) ? f($ctrl['ptMonthly'] ?? 200) : 0.0;
        $lwfMonth = (int) f($ctrl['lwfMonth'] ?? 0);
        $exitMonth = $exitTs !== false ? (int) gmdate('n', $exitTs) : 0;
        $lwf = (b($ctrl['lwfEnabled'] ?? 'Yes') && $lwfAp && ($lwfMonth === 0 || $lwfMonth === $exitMonth))
            ? f($ctrl['lwfEmpAmt'] ?? 20) : 0.0;

        $noDeductionRuleApplied = $paid < 15.0;
        $advApplied = $adv;
        $noticeApplied = $notice;
        $lopDedApplied = $lopDed;
        $otherApplied = $otherTotal;
        if ($noDeductionRuleApplied) {
            $pfEE = 0.0;
            $esiEE = 0.0;
            $pt = 0.0;
            $lwf = 0.0;
            $advApplied = 0.0;
            $noticeApplied = 0.0;
            $lopDedApplied = 0.0;
            $otherApplied = 0.0;
        }

        $statutory = $pfEE + $esiEE + $pt + $lwf;
        $te = $earned + $enc + $bonus + $gratuityAmount;
        $td = $lopDedApplied + $advApplied + $noticeApplied + $otherApplied + $statutory;
        $final = $te - $td;
        $id = $eid.'-'.time();

        $row = [
            'id' => $id,
            'empId' => $eid,
            'employeeName' => $name,
            'resignationDate' => $resignation,
            'exitDate' => $exit,
            'gross' => round($gross, 2),
            'paidDays' => round($paid, 2),
            'lopDays' => round($lop, 2),
            'elDays' => round($el, 2),
            'bonus' => round($bonus, 2),
            'incentiveAmount' => round($incentiveAmount, 2),
            'gratuity' => round($gratuityAmount, 2),
            'gratuityYears' => round(f($gratuityInfo['years'] ?? 0), 2),
            'advance' => round($advApplied, 2),
            'advanceItems' => $noDeductionRuleApplied ? [] : ($advanceOutstanding['items'] ?? []),
            'loanItems' => $noDeductionRuleApplied ? [] : ($loanOutstanding['items'] ?? []),
            'notice' => round($noticeApplied, 2),
            'otherDeductions' => round($otherApplied, 2),
            'otherDeductionItems' => $noDeductionRuleApplied ? [] : $otherItems,
            'pfEE' => round($pfEE, 2),
            'esiEE' => round($esiEE, 2),
            'pt' => round($pt, 2),
            'lwf' => round($lwf, 2),
            'esiApplicable' => (! empty($stat['esiApplicable']) && (! $noDeductionRuleApplied)),
            'statutoryDeductions' => round($statutory, 2),
            'noDeductionsRuleApplied' => $noDeductionRuleApplied,
            'monthDays' => $monthDays,
            'perDay' => round($pd, 2),
            'earnedGross' => round($earned, 2),
            'earned' => round($earned, 2),
            'lopDeduction' => round($lopDedApplied, 2),
            'leaveEncashment' => round($enc, 2),
            'totalEarnings' => round($te, 2),
            'totalDeductions' => round($td, 2),
            'finalPay' => round($final, 2),
            'generatedAt' => now_iso(),
        ];

        $this->sheets->put('fnf_sheet', $id, $row, [
            'empId' => $eid,
            'employeeName' => $name,
            'resignationDate' => $resignation,
            'exitDate' => $exit,
            'finalPay' => $row['finalPay'],
            'generatedAt' => $row['generatedAt'],
        ]);

        mail_fnf_event($clientId, $row);

        return $row;
    }
}
