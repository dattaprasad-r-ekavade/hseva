<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithHrApi;
use Tests\Concerns\ResetsHrDatabases;
use Tests\TestCase;

class FaceAttendanceContractTest extends TestCase
{
    use InteractsWithHrApi;
    use ResetsHrDatabases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    public function test_face_attendance_settings_get_and_put(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);
        $headers = $this->tenantHeaders($token);

        $this->withHeaders($headers)
            ->getJson('/api/face-attendance/settings')
            ->assertOk()
            ->assertJsonPath('row.inAllowedFrom', '08:00')
            ->assertJsonPath('row.timezone', 'Asia/Kolkata');

        $this->withHeaders($headers)
            ->putJson('/api/face-attendance/settings', [
                'graceTime' => 15,
                'faceMatchThreshold' => 0.5,
            ])
            ->assertOk()
            ->assertJsonPath('row.graceTime', 15)
            ->assertJsonPath('row.faceMatchThreshold', 0.5);
    }

    public function test_face_attendance_registrations_and_sheet(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);
        $headers = $this->tenantHeaders($token);

        $this->withHeaders($headers)
            ->postJson('/api/employees', [
                'id' => 'E001',
                'name' => 'Jane Doe',
                'status' => 'Active',
                'dept' => 'HR',
                'desig' => 'Exec',
                'type' => 'Full-time',
                'mobile' => '9999999999',
                'email' => 'jane@example.com',
                'doj' => '2024-01-01',
                'pf' => 'Yes',
                'uan' => '',
                'esi' => 'Yes',
                'esiNo' => '',
                'baseCtc' => 30000,
            ])
            ->assertCreated();

        $this->withHeaders($headers)
            ->getJson('/api/face-attendance/registrations')
            ->assertOk()
            ->assertJsonPath('rows', []);

        $this->withHeaders($headers)
            ->getJson('/api/face-attendance/sheet?month=6&year=2026')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }
}
