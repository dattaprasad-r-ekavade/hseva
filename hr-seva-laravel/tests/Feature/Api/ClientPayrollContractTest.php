<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithHrApi;
use Tests\Concerns\ResetsHrDatabases;
use Tests\TestCase;

class ClientPayrollContractTest extends TestCase
{
    use InteractsWithHrApi;
    use ResetsHrDatabases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    public function test_create_client_and_list(): void
    {
        $token = $this->superAdminToken();

        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/clients', [
                'companyName' => 'Acme HR',
                'companyAddress' => '1 Main St',
                'companyRegNo' => 'REG',
                'companyPan' => 'PAN',
                'companyTan' => 'TAN',
                'companyGstin' => 'GST',
                'companyContactNo' => '9999999999',
                'userId' => 'acmeadmin',
                'userPassword' => 'secret123',
            ]);

        $create->assertCreated()->assertJsonPath('row.companyName', 'Acme HR');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/clients')
            ->assertOk()
            ->assertJsonPath('rows.0.companyName', 'Acme HR');
    }

    public function test_payroll_overrides_endpoint(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $this->withHeaders($this->tenantHeaders($token))
            ->getJson('/api/payroll/overrides')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }

    public function test_employees_requires_tenant_context(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $this->withHeaders($this->tenantHeaders($token))
            ->getJson('/api/employees')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }

    public function test_control_settings_contract(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $this->withHeaders($this->tenantHeaders($token))
            ->getJson('/api/control')
            ->assertOk()
            ->assertJsonStructure(['__configured', '__lastSaved']);
    }
}
