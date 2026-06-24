<?php

namespace App\Services\Auth;

use App\Services\Tenant\TenantManager;
use App\Support\HrSevaDefaults;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function __construct(
        private JwtService $jwt,
        private TenantManager $tenants,
    ) {}

    public function login(string $username, string $password): array
    {
        return auth_login(strtolower(trim($username)), trim($password));
    }

    public function session(?array $token): array
    {
        if (! $token) {
            return ['valid' => false];
        }

        return [
            'valid' => true,
            'user' => [
                'username' => (string) ($token['username'] ?? $token['sub'] ?? ''),
                'name' => (string) ($token['name'] ?? $token['username'] ?? ''),
                'role' => (string) ($token['role'] ?? ''),
                'clientId' => (int) ($token['clientId'] ?? 0),
                'empId' => (string) ($token['empId'] ?? ''),
            ],
        ];
    }

    public function seedSuperAdmins(): void
    {
        $users = array_map(fn ($u) => [
            'username' => $u['username'],
            'password' => $u['password'],
            'passwordHash' => password_hash($u['password'], PASSWORD_DEFAULT),
            'name' => $u['name'],
            'role' => $u['role'],
        ], HrSevaDefaults::AUTH_USERS);

        kv_set_on(central_db(), 'auth_users', $users);
    }
}
