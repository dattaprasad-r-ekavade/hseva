<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithHrApi;
use Tests\Concerns\ResetsHrDatabases;
use Tests\TestCase;

class LeavesContractTest extends TestCase
{
    use InteractsWithHrApi;
    use ResetsHrDatabases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    public function test_create_and_list_leave(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $create = $this->withHeaders($this->tenantHeaders($token))
            ->postJson('/api/leaves', [
                'empId' => 'E001',
                'empName' => 'Jane Doe',
                'fromDate' => '2026-06-01',
                'toDate' => '2026-06-02',
                'days' => 2,
                'leaveType' => 'CL',
                'reason' => 'Personal',
                'dept' => 'HR',
                'desig' => 'Exec',
            ]);

        $create->assertCreated()
            ->assertJsonPath('row.empId', 'E001')
            ->assertJsonPath('row.leaveType', 'CL');

        $this->withHeaders($this->tenantHeaders($token))
            ->getJson('/api/leaves?month=6&year=2026')
            ->assertOk()
            ->assertJsonPath('rows.0.empId', 'E001');
    }

    public function test_leave_summary(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $this->withHeaders($this->tenantHeaders($token))
            ->postJson('/api/leaves', [
                'empId' => 'E001',
                'empName' => 'Jane Doe',
                'fromDate' => '2026-06-01',
                'toDate' => '2026-06-01',
                'days' => 1,
                'leaveType' => 'CL',
                'reason' => 'Personal',
            ]);

        $this->withHeaders($this->tenantHeaders($token))
            ->getJson('/api/leaves/summary?month=6&year=2026')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }
}
