<?php

namespace Tests\Feature\Api;

use App\Services\Storage\SheetStorageService;
use App\Services\Storage\TenantSettingsService;
use App\Services\Tenant\TenantManager;
use Tests\Concerns\InteractsWithHrApi;
use Tests\Concerns\ResetsHrDatabases;
use Tests\TestCase;

class NormalizedStorageContractTest extends TestCase
{
    use InteractsWithHrApi;
    use ResetsHrDatabases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    public function test_control_settings_write_uses_tenant_settings_not_app_kv(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $this->withHeaders($this->tenantHeaders($token))
            ->putJson('/api/control', ['pfEmpPct' => 12.5])
            ->assertOk();

        $tenants = app(TenantManager::class);
        $tenants->setClientId(1);

        $this->assertTrue(
            $tenants->tenant()->table('tenant_settings')->where('key', 'control_settings')->exists()
        );
        $this->assertFalse(
            $tenants->tenant()->table('app_kv')->where('key', 'control_settings')->exists()
        );
    }

    public function test_payroll_overrides_use_normalized_table(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $this->withHeaders($this->tenantHeaders($token))
            ->putJson('/api/payroll/overrides/E001', [
                'gross' => 30000,
                'pfAppl' => true,
                'esiAppl' => true,
                'ptAppl' => true,
                'lwfAppl' => true,
            ])
            ->assertOk();

        $tenants = app(TenantManager::class);
        $tenants->setClientId(1);

        $this->assertTrue(
            $tenants->tenant()->table('payroll_overrides')->where('emp_id', 'E001')->exists()
        );
        $this->assertFalse(
            $tenants->tenant()->table('app_kv')->where('key', 'payroll_overrides')->exists()
        );
    }

    public function test_attendance_daily_uses_normalized_table(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $this->withHeaders($this->tenantHeaders($token))
            ->postJson('/api/attendance/daily/upsert', [
                'month' => 6,
                'year' => 2026,
                'records' => [
                    ['empId' => 'E001', 'date' => '2026-06-01', 'status' => 'P'],
                ],
            ])
            ->assertOk();

        $tenants = app(TenantManager::class);
        $tenants->setClientId(1);

        $this->assertTrue(
            $tenants->tenant()->table('attendance_daily')->where('year', 2026)->where('month', 6)->exists()
        );
        $this->assertFalse(
            $tenants->tenant()->table('app_kv')->where('key', 'attendance_daily_2026-06')->exists()
        );
    }
}
