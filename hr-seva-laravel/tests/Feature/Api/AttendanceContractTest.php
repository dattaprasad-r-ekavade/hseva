<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithHrApi;
use Tests\Concerns\ResetsHrDatabases;
use Tests\TestCase;

class AttendanceContractTest extends TestCase
{
    use InteractsWithHrApi;
    use ResetsHrDatabases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    public function test_daily_attendance_upsert_and_list(): void
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
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->withHeaders($this->tenantHeaders($token))
            ->getJson('/api/attendance/daily?month=6&year=2026')
            ->assertOk()
            ->assertJsonPath('rows.0.empId', 'E001')
            ->assertJsonPath('rows.0.status', 'P');
    }

    public function test_attendance_sheets_index(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);

        $this->withHeaders($this->tenantHeaders($token))
            ->getJson('/api/attendance/sheets')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }
}
