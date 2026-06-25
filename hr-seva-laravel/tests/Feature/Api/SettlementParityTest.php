<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithHrApi;
use Tests\Concerns\ResetsHrDatabases;
use Tests\Concerns\SeedsHrWorkflow;
use Tests\TestCase;

class SettlementParityTest extends TestCase
{
    use InteractsWithHrApi;
    use ResetsHrDatabases;
    use SeedsHrWorkflow;

    private const MONTH = 6;

    private const YEAR = 2026;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    public function test_bonus_preview_and_save_round_trip(): void
    {
        $token = $this->superAdminToken();
        [, $headers] = $this->tenantContext($token);
        $empId = $this->seedEmployee($headers);

        $this->withHeaders($headers)
            ->putJson('/api/control', [
                'bonusEnabled' => 'Yes',
                'bonusMinimumWage' => 7000,
                'bonusMultiplierMonths' => 12,
                'bonusPercent' => 8.33,
            ])
            ->assertOk();

        $preview = $this->withHeaders($headers)
            ->postJson('/api/bonus/generate', ['month' => self::MONTH, 'year' => self::YEAR])
            ->assertOk()
            ->json('sheet');

        $this->assertSame('2026-06', $preview['period']);
        $row = collect($preview['rows'])->firstWhere('empId', $empId);
        $this->assertNotNull($row);
        $this->assertSame(7000.0, (float) $row['minimumWage']);
        $this->assertGreaterThan(0, (float) $row['bonusAmount']);

        $saved = $this->withHeaders($headers)
            ->postJson('/api/bonus/sheets', [
                'month' => self::MONTH,
                'year' => self::YEAR,
                'rows' => $preview['rows'],
            ])
            ->assertOk()
            ->json('sheet');

        $this->assertSame('2026-06', $saved['period']);
        $this->assertSame(round((float) $row['bonusAmount'], 2), (float) collect($saved['rows'])->firstWhere('empId', $empId)['bonusAmount']);
    }

    public function test_gratuity_after_five_years_mode_generates_amount(): void
    {
        $token = $this->superAdminToken();
        [, $headers] = $this->tenantContext($token);
        $empId = $this->seedEmployee($headers, ['baseCtc' => 30000]);

        $sheet = $this->withHeaders($headers)
            ->postJson('/api/gratuity/generate', [
                'empId' => $empId,
                'years' => 6,
            ])
            ->assertOk()
            ->json('sheet');

        $this->assertSame($empId, $sheet['empId']);
        $this->assertSame('after_5yr', $sheet['mode']);
        $this->assertGreaterThan(0, (float) $sheet['gratuityAmount']);
    }

    public function test_fnf_settlement_uses_payroll_gross_and_applies_statutory_deductions(): void
    {
        $token = $this->superAdminToken();
        [, $headers] = $this->tenantContext($token);
        $empId = $this->seedEmployee($headers, ['baseCtc' => 30000]);
        $this->seedAttendanceSheet($headers, self::MONTH, self::YEAR);
        $payroll = $this->seedPayrollSheet($headers, self::MONTH, self::YEAR);
        $payRow = collect($payroll['rows'])->firstWhere('empId', $empId);
        $this->assertNotNull($payRow);

        $fnf = $this->withHeaders($headers)
            ->postJson('/api/fnf/generate', [
                'empId' => $empId,
                'exitDate' => '2026-06-30',
                'gross' => (float) $payRow['gross'],
                'paidDays' => 30,
                'lopDays' => 0,
                'elDays' => 0,
            ])
            ->assertOk()
            ->json('sheet');

        $this->assertSame($empId, $fnf['empId']);
        $this->assertGreaterThan(0, (float) $fnf['earnedGross']);
        $this->assertGreaterThan(0, (float) $fnf['pfEE']);
        $this->assertGreaterThan(0, (float) $fnf['finalPay']);
        $this->assertEqualsWithDelta(
            (float) $fnf['finalPay'],
            (float) $fnf['totalEarnings'] - (float) $fnf['totalDeductions'],
            0.01
        );
    }

    public function test_fnf_skips_deductions_when_paid_days_below_threshold(): void
    {
        $token = $this->superAdminToken();
        [, $headers] = $this->tenantContext($token);
        $empId = $this->seedEmployee($headers, ['baseCtc' => 30000]);

        $fnf = $this->withHeaders($headers)
            ->postJson('/api/fnf/generate', [
                'empId' => $empId,
                'exitDate' => '2026-06-14',
                'gross' => 27000,
                'paidDays' => 10,
                'lopDays' => 0,
                'elDays' => 0,
            ])
            ->assertOk()
            ->json('sheet');

        $this->assertTrue((bool) $fnf['noDeductionsRuleApplied']);
        $this->assertSame(0.0, (float) $fnf['pfEE']);
        $this->assertSame(0.0, (float) $fnf['statutoryDeductions']);
    }

    public function test_settlement_generators_are_bound_in_container(): void
    {
        $this->assertTrue(app()->bound(\App\Services\Fnf\FnfGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Gratuity\GratuityGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Bonus\BonusGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Payslips\PayslipGenerator::class));
    }
}
