<?php

namespace App\Services\Auth;

class AuthService
{
    public function __construct(
        private AuthLoginRepository $loginRepository,
        private AuthUsersRepository $users,
    ) {}

    public function login(string $username, string $password): array
    {
        return $this->loginRepository->login(strtolower(trim($username)), trim($password));
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
        $this->users->seedSuperAdmins();
    }

    public function forgot(array $data): array
    {
        return $this->loginRepository->forgot($data);
    }
}
