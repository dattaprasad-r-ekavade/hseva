<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithHrApi;
use Tests\Concerns\ResetsHrDatabases;
use Tests\Concerns\SeedsHrWorkflow;
use Tests\TestCase;

class PayrollPipelineParityTest extends TestCase
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

    public function test_full_payroll_pipeline_generates_consistent_statutory_totals(): void
    {
        $token = $this->superAdminToken();
        [, $headers] = $this->tenantContext($token);
        $empId = $this->seedEmployee($headers, ['id' => 'EMP001', 'baseCtc' => 30000]);

        $attendance = $this->seedAttendanceSheet($headers, self::MONTH, self::YEAR);
        $this->assertSame('2026-06', $attendance['period']);
        $this->assertGreaterThanOrEqual(1, $attendance['rowCount']);

        $payroll = $this->seedPayrollSheet($headers, self::MONTH, self::YEAR);
        $payRow = collect($payroll['rows'])->firstWhere('empId', $empId);
        $this->assertNotNull($payRow);
        $this->assertSame(27000.0, (float) $payRow['gross']);
        $this->assertGreaterThan(0, (float) $payRow['pfEE']);
        $this->assertGreaterThan(0, (float) $payRow['netPayable']);

        $pf = $this->withHeaders($headers)
            ->postJson('/api/pf-sheet/generate', ['month' => self::MONTH, 'year' => self::YEAR])
            ->assertOk()
            ->json('sheet');
        $pfRow = collect($pf['rows'])->firstWhere('Emp_ID', $empId);
        $this->assertNotNull($pfRow);
        $this->assertSame(round((float) $payRow['pfEE'], 2), (float) $pfRow['PF_EE']);

        $pfReturn = $this->withHeaders($headers)
            ->postJson('/api/pf-return/generate', ['month' => self::MONTH, 'year' => self::YEAR])
            ->assertOk()
            ->json('sheet');
        $this->assertGreaterThan(0, count($pfReturn['rows'] ?? []));

        $ecr = $this->withHeaders($headers)
            ->postJson('/api/ecr-sheet/generate', ['month' => self::MONTH, 'year' => self::YEAR])
            ->assertOk()
            ->json('sheet');
        $this->assertGreaterThan(0, count($ecr['rows'] ?? []));
        $this->assertSame(
            round((float) $pfRow['PF_EE'], 2),
            (float) collect($ecr['rows'])->first()['EPF_CONTRI_REMITTED']
        );

        $esic = $this->withHeaders($headers)
            ->postJson('/api/esic-sheet/generate', ['month' => self::MONTH, 'year' => self::YEAR])
            ->assertOk()
            ->json('sheet');
        if (! empty($esic['rows'])) {
            $this->withHeaders($headers)
                ->postJson('/api/esic-return/generate', ['month' => self::MONTH, 'year' => self::YEAR])
                ->assertOk()
                ->assertJsonStructure(['sheet' => ['rows', 'period']]);
        }

        $payslip = $this->withHeaders($headers)
            ->postJson('/api/payslips/generate', [
                'month' => self::MONTH,
                'year' => self::YEAR,
                'empId' => $empId,
                'format' => 'html',
            ])
            ->assertOk()
            ->json('sheet');
        $this->assertSame($empId, $payslip['empId']);
        $this->assertSame(round((float) $payRow['netPayable'], 2), round((float) ($payslip['data']['totals']['netPay'] ?? 0), 2));
    }

    public function test_payroll_requires_attendance_sheet_first(): void
    {
        $token = $this->superAdminToken();
        [, $headers] = $this->tenantContext($token);
        $this->seedEmployee($headers);

        $this->withHeaders($headers)
            ->postJson('/api/payroll/generate', ['month' => self::MONTH, 'year' => self::YEAR])
            ->assertStatus(400)
            ->assertJsonPath('detail', 'Attendance sheet not found for selected month. Generate Attendance Sheet first.');
    }

    public function test_generator_services_are_bound_in_container(): void
    {
        $this->assertTrue(app()->bound(\App\Services\Attendance\AttendanceGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Payroll\PayrollGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Payroll\PfSheetGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Payroll\PfReturnGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Payroll\EsicSheetGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Payroll\EcrSheetGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Payroll\EsicReturnGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Sheets\SheetCrudService::class));
        $this->assertTrue(app()->bound(\App\Services\Fnf\FnfGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Gratuity\GratuityGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Bonus\BonusGenerator::class));
        $this->assertTrue(app()->bound(\App\Services\Payslips\PayslipGenerator::class));
    }
}
