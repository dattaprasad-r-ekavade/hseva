<?php

namespace App\Services\Payroll;

class StatutoryCalculator
{
    public function splitCtc(float $base, array $control): array
    {
        $b = $base * f($control['ctcBasicPct'] ?? 50) / 100;
        $h = $base * f($control['ctcHraPct'] ?? 10) / 100;
        $v = $base * f($control['ctcConvPct'] ?? 0) / 100;
        $d = $base * f($control['ctcDaPct'] ?? 30) / 100;
        $e = $base * f($control['ctcEduPct'] ?? 0) / 100;
        $s = $base * f($control['ctcSpecialPct'] ?? 0) / 100;

        return [
            'basic' => $b,
            'hra' => $h,
            'convey' => $v,
            'da' => $d,
            'edu' => $e,
            'special' => $s,
            'gross' => $b + $h + $v + $d + $e + $s,
        ];
    }

    public function controlOtherDeductionBreakup(array $control): array
    {
        $rows = $control['otherDeductionRows'] ?? [];
        if (! is_array($rows)) {
            return [];
        }
        $items = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = s($row['name'] ?? ($row['label'] ?? ''));
            if ($name === '') {
                continue;
            }
            $amt = f($row['amount'] ?? ($row['monthly'] ?? ($row['value'] ?? 0)));
            if ($amt <= 0) {
                continue;
            }
            if (! isset($items[$name])) {
                $items[$name] = 0.0;
            }
            $items[$name] += $amt;
        }
        $out = [];
        foreach ($items as $name => $amt) {
            $out[] = ['name' => $name, 'amount' => round($amt, 2)];
        }

        return $out;
    }

    public function ctrlNum(array $control, string $key): float
    {
        return f($control[$key] ?? (DEFAULT_CONTROL[$key] ?? 0));
    }

    public function ctrlBool(array $control, string $key): bool
    {
        return b($control[$key] ?? (DEFAULT_CONTROL[$key] ?? false));
    }

    public function esiWageLimit(array $control): float
    {
        return max(0.0, $this->ctrlNum($control, 'esiWageLimit'));
    }

    public function payrollStatutoryCalc(array $control, float $gross, float $earned, bool $pfAp, bool $esiAp): array
    {
        $calcBase = max(0.0, $earned);
        $earnedParts = $this->splitCtc($calcBase, $control);
        $pfBase = f($earnedParts['basic'] ?? 0) + (f($earnedParts['basic'] ?? 0) * $this->ctrlNum($control, 'daPctBasic') / 100.0);
        $esiLimit = $this->esiWageLimit($control);
        $esiEligibleByWage = $esiLimit > 0 ? ($calcBase <= $esiLimit) : false;
        $esiApplicable = $esiAp && $calcBase > 0 && $esiEligibleByWage;
        $pfCapEnabled = $this->ctrlBool($control, 'pfWageCapEnabled');
        $pfOnEsiPct = max(0.0, $this->ctrlNum($control, 'pfOnEsiPct'));
        if ($esiApplicable) {
            $statutoryBase = $calcBase * $pfOnEsiPct / 100.0;
            $pfWages = $statutoryBase;
            $esiWages = $statutoryBase;
        } else {
            $pfThreshold = $esiLimit;
            $pfWages = ($pfCapEnabled && $pfThreshold > 0 && $calcBase > $pfThreshold)
                ? $this->ctrlNum($control, 'pfWageCapAmount')
                : $pfBase;
            $esiWages = $calcBase;
        }
        $pfEE = $pfAp ? ($pfWages * $this->ctrlNum($control, 'pfEmpPct') / 100.0) : 0.0;
        $pfER = $pfAp ? ($pfWages * $this->ctrlNum($control, 'pfErPct') / 100.0) : 0.0;
        $esiEE = $esiApplicable ? ($esiWages * $this->ctrlNum($control, 'esiEmpPct') / 100.0) : 0.0;
        $esiER = $esiApplicable ? ($esiWages * $this->ctrlNum($control, 'esiErPct') / 100.0) : 0.0;

        return [
            'calcBase' => round($calcBase, 2),
            'statutoryBase' => round($esiApplicable ? $esiWages : $calcBase, 2),
            'pfBase' => round($pfBase, 2),
            'pfWages' => round($pfWages, 2),
            'esiWages' => round($esiWages, 2),
            'pfEE' => round($pfEE, 2),
            'pfER' => round($pfER, 2),
            'esiEE' => round($esiEE, 2),
            'esiER' => round($esiER, 2),
            'esiLimit' => round($esiLimit, 2),
            'esiApplicable' => $esiApplicable,
        ];
    }
}
