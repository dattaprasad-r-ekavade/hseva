<?php

namespace Tests\Feature;

use App\Services\Tenant\TenantManager;
use Tests\TestCase;

class TenantManagerConfigTest extends TestCase
{
    public function test_default_drivers_are_sqlite(): void
    {
        $mgr = app(TenantManager::class);

        $this->assertSame('sqlite', $mgr->centralDriver());
        $this->assertSame('sqlite', $mgr->tenantDriver());
        $this->assertFalse($mgr->usesMysql());
    }

    public function test_mysql_tenant_database_name(): void
    {
        config([
            'hrseva.mysql.tenant_database_prefix' => 'hr_seva_tenant_',
        ]);

        $mgr = app(TenantManager::class);

        $this->assertSame('hr_seva_tenant_42', $mgr->tenantDatabaseName(42));
    }
}
