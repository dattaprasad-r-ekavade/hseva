<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithHrApi;
use Tests\Concerns\ResetsHrDatabases;
use Tests\TestCase;

class SubscriptionsContractTest extends TestCase
{
    use InteractsWithHrApi;
    use ResetsHrDatabases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    public function test_list_subscription_plans(): void
    {
        $token = $this->superAdminToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/subscription-plans')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }

    public function test_create_subscription_for_client(): void
    {
        $token = $this->superAdminToken();
        $clientId = $this->createTestClient($token);

        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/subscriptions', [
                'clientId' => $clientId,
                'planName' => 'Standard Annual',
                'startDate' => '2026-01-01',
                'endDate' => '2026-12-31',
                'renewalDate' => '2026-12-01',
                'status' => 'Active',
                'amount' => 12000,
            ]);

        $create->assertCreated()
            ->assertJsonPath('row.planName', 'Standard Annual');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/subscriptions')
            ->assertOk()
            ->assertJsonPath('rows.0.clientId', $clientId);
    }
}
