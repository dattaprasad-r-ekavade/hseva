<?php

namespace Tests\Concerns;

trait SeedsHrWorkflow
{
    protected function seedEmployee(array $headers, array $overrides = []): string
    {
        $payload = array_merge([
            'id' => 'EMP001',
            'name' => 'Test Employee',
            'status' => 'Active',
            'dept' => 'Engineering',
            'desig' => 'Developer',
            'type' => 'Full-time',
            'mobile' => '9999999999',
            'email' => 'emp@example.com',
            'doj' => '2024-01-01',
            'pf' => 'Yes',
            'uan' => '100000000001',
            'esi' => 'Yes',
            'esiNo' => '1234567890',
            'baseCtc' => 30000,
        ], $overrides);

        $this->withHeaders($headers)
            ->postJson('/api/employees', $payload)
            ->assertCreated()
            ->assertJsonPath('row.id', $payload['id']);

        return (string) $payload['id'];
    }

    protected function seedAttendanceSheet(array $headers, int $month = 6, int $year = 2026): array
    {
        $response = $this->withHeaders($headers)
            ->postJson('/api/attendance/generate', [
                'month' => $month,
                'year' => $year,
                'fillDefault' => true,
                'sundayWeeklyOff' => true,
            ]);

        $response->assertOk()->assertJsonStructure(['sheet' => ['id', 'rows', 'period', 'rowCount']]);

        return $response->json('sheet');
    }

    protected function seedPayrollSheet(array $headers, int $month = 6, int $year = 2026, string $absentMode = 'LOP'): array
    {
        $response = $this->withHeaders($headers)
            ->postJson('/api/payroll/generate', [
                'month' => $month,
                'year' => $year,
                'absentMode' => $absentMode,
            ]);

        $response->assertOk()->assertJsonStructure(['sheet' => ['id', 'rows', 'period', 'rowCount']]);

        return $response->json('sheet');
    }
}
