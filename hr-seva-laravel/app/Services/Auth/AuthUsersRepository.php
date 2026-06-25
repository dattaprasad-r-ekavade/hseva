<?php

namespace App\Services\Auth;

use App\Support\HrSevaDefaults;

class AuthUsersRepository
{
    public function all(): array
    {
        $st = central_db()->prepare('SELECT value FROM app_kv WHERE key=?');
        $st->execute(['auth_users']);
        $r = $st->fetch();
        $x = $r ? json_decode((string) $r['value'], true) : HrSevaDefaults::AUTH_USERS;
        $rows = is_array($x) ? $x : HrSevaDefaults::AUTH_USERS;
        $hasAdmin = false;
        $hasAdminHrseva = false;

        foreach ($rows as &$u) {
            if (empty($u['role'])) {
                $u['role'] = 'client';
            }
            if (strtolower((string) ($u['username'] ?? '')) === 'admin') {
                $hasAdmin = true;
                if (empty($u['role'])) {
                    $u['role'] = 'super_admin';
                }
            }
            if (strtolower((string) ($u['username'] ?? '')) === 'admin@hrseva.com') {
                $hasAdminHrseva = true;
                if (empty($u['role'])) {
                    $u['role'] = 'super_admin';
                }
            }
        }
        unset($u);

        if (! $hasAdmin) {
            $rows[] = ['username' => 'admin', 'password' => '123456', 'name' => 'Admin', 'role' => 'super_admin'];
        }
        if (! $hasAdminHrseva) {
            $rows[] = ['username' => 'admin@hrseva.com', 'password' => '123456', 'name' => 'Admin', 'role' => 'super_admin'];
        }

        return $rows;
    }

    public function save(array $rows): void
    {
        kv_set_on(central_db(), 'auth_users', array_values($rows));
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

        $this->save($users);
    }
}
