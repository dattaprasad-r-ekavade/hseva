<?php

namespace Tests\Concerns;

trait InteractsWithHrApi
{
    protected function superAdminToken(): string
    {
        return (string) $this->postJson('/api/auth/login', [
            'username' => 'admin@hrseva.com',
            'password' => '123456',
        ])->json('token');
    }

    protected function createTestClient(string $token, string $userId = 'acmeadmin'): int
    {
        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/clients', [
                'companyName' => 'Acme HR',
                'companyAddress' => '1 Main St',
                'companyRegNo' => 'REG',
                'companyPan' => 'PAN',
                'companyTan' => 'TAN',
                'companyGstin' => 'GST',
                'companyContactNo' => '9999999999',
                'userId' => $userId,
                'userPassword' => 'secret123',
            ]);

        $res->assertCreated();

        return (int) $res->json('row.id');
    }

    protected function tenantHeaders(string $token, int $clientId = 1): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'X-Client-Id' => (string) $clientId,
        ];
    }
}
