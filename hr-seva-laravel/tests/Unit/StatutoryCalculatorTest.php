<?php

namespace Tests\Unit;

use App\Services\Payroll\StatutoryCalculator;
use Tests\TestCase;

class StatutoryCalculatorTest extends TestCase
{
    public function test_split_ctc_defaults(): void
    {
        $calc = new StatutoryCalculator;
        $parts = $calc->splitCtc(100000, DEFAULT_CONTROL);

        $this->assertSame(50000.0, $parts['basic']);
        $this->assertSame(10000.0, $parts['hra']);
        $this->assertSame(30000.0, $parts['da']);
        $this->assertSame(90000.0, $parts['gross']);
    }

    public function test_payroll_statutory_calc_pf_and_esi(): void
    {
        $calc = new StatutoryCalculator;
        $ctrl = array_merge(DEFAULT_CONTROL, [
            'esiWageLimit' => 21000,
            'pfEmpPct' => 12,
            'pfErPct' => 12,
            'esiEmpPct' => 0.75,
            'esiErPct' => 3.25,
        ]);

        $stat = $calc->payrollStatutoryCalc($ctrl, 20000, 20000, true, true);

        $this->assertTrue($stat['esiApplicable']);
        $this->assertGreaterThan(0, $stat['pfEE']);
        $this->assertGreaterThan(0, $stat['esiEE']);
        $this->assertSame(20000.0, $stat['calcBase']);
    }

    public function test_control_other_deduction_breakup(): void
    {
        $calc = new StatutoryCalculator;
        $items = $calc->controlOtherDeductionBreakup([
            'otherDeductionRows' => [
                ['name' => 'Canteen', 'amount' => 200],
                ['name' => 'Canteen', 'amount' => 100],
            ],
        ]);

        $this->assertCount(1, $items);
        $this->assertSame('Canteen', $items[0]['name']);
        $this->assertSame(300.0, $items[0]['amount']);
    }
}
