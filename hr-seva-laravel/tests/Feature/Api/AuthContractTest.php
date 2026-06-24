<?php

namespace Tests\Feature\Api;

use Tests\Concerns\ResetsHrDatabases;
use Tests\TestCase;

class AuthContractTest extends TestCase
{
    use ResetsHrDatabases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    public function test_health_endpoint(): void
    {
        $this->getJson('/api/health')->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_super_admin_login_contract(): void
    {
        $res = $this->postJson('/api/auth/login', [
            'username' => 'admin@hrseva.com',
            'password' => '123456',
        ]);

        $res->assertOk()
            ->assertJsonStructure(['ok', 'token', 'user' => ['username', 'name', 'role', 'clientId']])
            ->assertJsonPath('user.role', 'super_admin');
    }

    public function test_session_without_token(): void
    {
        $this->getJson('/api/auth/session')->assertOk()->assertJson(['valid' => false]);
    }

    public function test_clients_requires_auth(): void
    {
        $this->getJson('/api/clients')->assertUnauthorized();
    }

    public function test_clients_list_with_super_admin(): void
    {
        $login = $this->postJson('/api/auth/login', [
            'username' => 'admin@hrseva.com',
            'password' => '123456',
        ])->json();

        $this->withHeader('Authorization', 'Bearer '.$login['token'])
            ->getJson('/api/clients')
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }
}
