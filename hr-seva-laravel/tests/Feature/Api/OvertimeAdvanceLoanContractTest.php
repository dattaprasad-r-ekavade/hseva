<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithHrApi;
use Tests\Concerns\ResetsHrDatabases;
use Tests\TestCase;

class OvertimeAdvanceLoanContractTest extends TestCase
{
    use InteractsWithHrApi;
    use ResetsHrDatabases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    public function test_overtime_list_and_clear(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);
        $headers = $this->tenantHeaders($token);

        $this->withHeaders($headers)
            ->getJson('/api/overtime')
            ->assertOk()
            ->assertJsonStructure(['rows', 'stats']);

        $this->withHeaders($headers)
            ->postJson('/api/overtime/clear')
            ->assertOk()
            ->assertJsonPath('status', 'cleared');
    }

    public function test_advances_list(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $this->withHeaders($this->tenantHeaders($token))
            ->getJson('/api/advances')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }

    public function test_loans_list(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $this->withHeaders($this->tenantHeaders($token))
            ->getJson('/api/loans')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }

    public function test_pf_return_sheets_index(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $this->withHeaders($this->tenantHeaders($token))
            ->getJson('/api/pf-return/sheets')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }

    public function test_esic_return_sheets_index(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $this->withHeaders($this->tenantHeaders($token))
            ->getJson('/api/esic-return/sheets')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }

    public function test_legacy_dispatch_returns_not_found(): void
    {
        $token = $this->superAdminToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/unknown-legacy-route')
            ->assertNotFound();
    }
}
