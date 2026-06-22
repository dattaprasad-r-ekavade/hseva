<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithHrApi;
use Tests\Concerns\ResetsHrDatabases;
use Tests\TestCase;

class ComplianceContractTest extends TestCase
{
    use InteractsWithHrApi;
    use ResetsHrDatabases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    public function test_compliance_tasks_list_and_upsert(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);
        $headers = $this->tenantHeaders($token);

        $this->withHeaders($headers)
            ->getJson('/api/compliance/tasks?month=6&year=2026')
            ->assertOk()
            ->assertJsonStructure(['rows'])
            ->assertJsonCount(4, 'rows');

        $this->withHeaders($headers)
            ->postJson('/api/compliance/tasks/upsert', [
                'month' => 6,
                'year' => 2026,
                'rows' => [
                    [
                        'dueDate' => '2026-06-15',
                        'task' => 'Custom compliance task',
                        'status' => 'In Progress',
                        'action' => 'View',
                        'notes' => 'Test note',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('rows.0.task', 'Custom compliance task');
    }

    public function test_compliance_challans_crud(): void
    {
        $token = $this->superAdminToken();
        $this->createTestClient($token);
        $headers = $this->tenantHeaders($token);

        $create = $this->withHeaders($headers)
            ->postJson('/api/compliance/challans', [
                'month' => 6,
                'year' => 2026,
                'type' => 'PF',
                'dueDate' => '2026-06-15',
                'status' => 'Pending',
                'amount' => 1500.50,
                'notes' => 'June PF',
            ]);

        $create->assertCreated()
            ->assertJsonPath('row.type', 'PF')
            ->assertJsonPath('row.amount', 1500.5);

        $id = (string) $create->json('row.id');
        $this->assertNotEmpty($id);

        $this->withHeaders($headers)
            ->getJson('/api/compliance/challans')
            ->assertOk()
            ->assertJsonFragment(['id' => $id, 'type' => 'PF']);

        $this->withHeaders($headers)
            ->deleteJson('/api/compliance/challans/'.$id)
            ->assertOk()
            ->assertJsonPath('status', 'deleted');
    }
}
