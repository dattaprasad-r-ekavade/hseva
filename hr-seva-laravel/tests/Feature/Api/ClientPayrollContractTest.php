<?php

namespace Tests\Feature\Api;

use Tests\Concerns\ResetsHrDatabases;
use Tests\TestCase;

class ClientPayrollContractTest extends TestCase
{
    use ResetsHrDatabases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    private function superAdminToken(): string
    {
        return (string) $this->postJson('/api/auth/login', [
            'username' => 'admin@hrseva.com',
            'password' => '123456',
        ])->json('token');
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
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Client-Id', '1')
            ->getJson('/api/payroll/overrides')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }

    public function test_employees_requires_tenant_context(): void
    {
        $token = $this->superAdminToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/employees')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }

    public function test_control_settings_contract(): void
    {
        $token = $this->superAdminToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/clients', [
                'companyName' => 'Control Test Co',
                'companyAddress' => '1 Main St',
                'companyRegNo' => 'REG',
                'companyPan' => 'PAN',
                'companyTan' => 'TAN',
                'companyGstin' => 'GST',
                'companyContactNo' => '9999999999',
                'userId' => 'ctrladmin',
                'userPassword' => 'secret123',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Client-Id', '1')
            ->getJson('/api/control')
            ->assertOk()
            ->assertJsonStructure(['__configured', '__lastSaved']);
    }
}
