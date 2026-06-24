<?php

namespace App\Services\Access;

class AccessRepository
{
    public function accessDefaultPermissions(): array
    {
        return [
            'dashboard' => true, 'clientModule' => true, 'employeeMaster' => true, 'employeeType' => true, 'salarySheet' => true, 'payslips' => true,
            'compliance' => true, 'attendance' => true, 'attendanceStatus' => true, 'leaveManagement' => true, 'fnf' => true, 'gratuity' => true, 'bonus' => true, 'incentive' => true, 'loan' => true, 'pfSheet' => true,
            'pfReturn' => true, 'esicSheet' => true, 'esicReturn' => true, 'ecrSheet' => true, 'controlPage' => true,
            'companyProfile' => true, 'subscriptions' => true, 'billing' => true, 'invoices' => true, 'accessControl' => false, 'shiftRoster' => true, 'advanceSalary' => true,
        ];
    }

    public function accessNormPermissions($raw): array
    {
        $base = $this->accessDefaultPermissions();
        $src = is_array($raw) ? $raw : [];
        foreach ($base as $k => $v) {
            $base[$k] = b($src[$k] ?? $v);
        }

        return $base;
    }

    public function accessGet(int $clientId): array
    {
        if ($clientId <= 0 || ! $this->clientExists($clientId)) {
            nf('Client not found');
        }
        $q = central_db()->prepare('SELECT permissions, access_type, updated_at FROM client_access WHERE client_id=?');
        $q->execute([$clientId]);
        $row = $q->fetch();
        if (! $row) {
            return ['clientId' => $clientId, 'accessType' => 'custom', 'permissions' => $this->accessDefaultPermissions(), '__updatedAt' => null];
        }
        $decoded = json_decode((string) $row['permissions'], true);
        $type = strtolower(s($row['access_type'] ?? 'custom', 'custom'));

        return ['clientId' => $clientId, 'accessType' => $type, 'permissions' => $this->accessNormPermissions($decoded), '__updatedAt' => (string) $row['updated_at']];
    }

    public function accessPut(int $clientId, array $payload): array
    {
        if ($clientId <= 0 || ! $this->clientExists($clientId)) {
            nf('Client not found');
        }
        $type = strtolower(s($payload['accessType'] ?? 'custom', 'custom'));
        $srcPerm = $payload['permissions'] ?? null;
        if (! is_array($srcPerm) || ! $srcPerm) {
            $srcPerm = $this->accessTypePermissions($type);
        }
        $perm = $this->accessNormPermissions($srcPerm);
        if ($type === '') {
            $type = 'custom';
        }
        $ts = now_iso();
        $st = central_db()->prepare('INSERT INTO client_access (client_id, permissions, access_type, updated_at) VALUES (?,?,?,?) ON CONFLICT(client_id) DO UPDATE SET permissions=excluded.permissions, access_type=excluded.access_type, updated_at=excluded.updated_at');
        $st->execute([$clientId, json_encode($perm, JSON_UNESCAPED_UNICODE), $type, $ts]);

        return ['clientId' => $clientId, 'accessType' => $type, 'permissions' => $perm, '__updatedAt' => $ts];
    }

    public function accessTypeRows(): array
    {
        $rows = central_db()->query('SELECT code, name, permissions, is_system, updated_at FROM access_types ORDER BY is_system DESC, name ASC')->fetchAll();

        return array_map(function ($r) {
            $perm = json_decode((string) ($r['permissions'] ?? '[]'), true);

            return [
                'code' => (string) $r['code'],
                'name' => (string) $r['name'],
                'isSystem' => ((int) ($r['is_system'] ?? 0)) === 1,
                'permissions' => $this->accessNormPermissions(is_array($perm) ? $perm : []),
                '__updatedAt' => (string) ($r['updated_at'] ?? ''),
            ];
        }, $rows);
    }

    public function accessTypeGet(string $code): ?array
    {
        $st = central_db()->prepare('SELECT code, name, permissions, is_system, updated_at FROM access_types WHERE code=? LIMIT 1');
        $st->execute([strtolower(trim($code))]);
        $r = $st->fetch();
        if (! $r) {
            return null;
        }
        $perm = json_decode((string) ($r['permissions'] ?? '[]'), true);

        return [
            'code' => (string) $r['code'],
            'name' => (string) $r['name'],
            'isSystem' => ((int) ($r['is_system'] ?? 0)) === 1,
            'permissions' => $this->accessNormPermissions(is_array($perm) ? $perm : []),
            '__updatedAt' => (string) ($r['updated_at'] ?? ''),
        ];
    }

    public function accessTypePermissions(string $code): array
    {
        $row = $this->accessTypeGet($code);
        if ($row) {
            return $this->accessNormPermissions($row['permissions'] ?? []);
        }

        return $this->accessDefaultPermissions();
    }

    public function accessTypeCreate(array $payload): array
    {
        $name = s($payload['name'] ?? '');
        if ($name === '') {
            bad('Access type name is required');
        }
        $perm = $this->accessNormPermissions($payload['permissions'] ?? []);
        $code = $this->accessTypeCodeFromName($name);
        $ts = now_iso();
        $st = central_db()->prepare('INSERT INTO access_types (code,name,permissions,is_system,created_at,updated_at) VALUES (?,?,?,?,?,?)');
        $st->execute([$code, $name, json_encode($perm, JSON_UNESCAPED_UNICODE), 0, $ts, $ts]);

        return $this->accessTypeGet($code) ?? ['code' => $code, 'name' => $name, 'isSystem' => false, 'permissions' => $perm, '__updatedAt' => $ts];
    }

    public function accessTypeUpdate(string $code, array $payload): array
    {
        $row = $this->accessTypeGet($code);
        if (! $row) {
            nf('Access type not found');
        }
        if (! empty($row['isSystem'])) {
            j(['detail' => 'System access type cannot be edited'], 409);
        }
        $name = s($payload['name'] ?? $row['name']);
        if ($name === '') {
            bad('Access type name is required');
        }
        $perm = $this->accessNormPermissions($payload['permissions'] ?? $row['permissions']);
        $ts = now_iso();
        $st = central_db()->prepare('UPDATE access_types SET name=?, permissions=?, updated_at=? WHERE code=?');
        $st->execute([$name, json_encode($perm, JSON_UNESCAPED_UNICODE), $ts, $row['code']]);

        return $this->accessTypeGet($row['code']) ?? ['code' => $row['code'], 'name' => $name, 'isSystem' => false, 'permissions' => $perm, '__updatedAt' => $ts];
    }

    public function accessTypeDelete(string $code): void
    {
        $row = $this->accessTypeGet($code);
        if (! $row) {
            nf('Access type not found');
        }
        if (! empty($row['isSystem'])) {
            j(['detail' => 'System access type cannot be deleted'], 409);
        }
        $st = central_db()->prepare('DELETE FROM access_types WHERE code=?');
        $st->execute([$row['code']]);
    }

    public function staffRoleNormPermissions($raw): array
    {
        return $this->accessNormPermissions(is_array($raw) ? $raw : []);
    }

    public function staffRoleCodeFromName(int $clientId, string $name): string
    {
        $base = strtolower(trim($name));
        $base = preg_replace('/[^a-z0-9]+/', '_', $base ?? '') ?? '';
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'role';
        }
        $code = 'role_'.$base;
        $i = 1;
        while ($this->staffRoleGet($clientId, $code) !== null) {
            $i++;
            $code = 'role_'.$base.'_'.$i;
        }

        return $code;
    }

    public function staffRoleGet(int $clientId, string $code): ?array
    {
        if ($clientId <= 0) {
            return null;
        }
        $q = central_db()->prepare('SELECT client_id, code, name, permissions, created_at, updated_at FROM staff_roles WHERE client_id=? AND code=? LIMIT 1');
        $q->execute([$clientId, strtolower(trim($code))]);
        $r = $q->fetch();
        if (! $r) {
            return null;
        }
        $perm = json_decode((string) ($r['permissions'] ?? '[]'), true);

        return [
            'clientId' => (int) $r['client_id'],
            'code' => (string) $r['code'],
            'name' => (string) $r['name'],
            'permissions' => $this->staffRoleNormPermissions(is_array($perm) ? $perm : []),
            '__createdAt' => (string) ($r['created_at'] ?? ''),
            '__updatedAt' => (string) ($r['updated_at'] ?? ''),
        ];
    }

    public function staffRoleRows(int $clientId): array
    {
        if ($clientId <= 0 || ! $this->clientExists($clientId)) {
            return [];
        }
        $q = central_db()->prepare('SELECT client_id, code, name, permissions, created_at, updated_at FROM staff_roles WHERE client_id=? ORDER BY name ASC');
        $q->execute([$clientId]);
        $rows = $q->fetchAll();

        return array_map(function ($r) {
            $perm = json_decode((string) ($r['permissions'] ?? '[]'), true);

            return [
                'clientId' => (int) $r['client_id'],
                'code' => (string) $r['code'],
                'name' => (string) $r['name'],
                'permissions' => $this->staffRoleNormPermissions(is_array($perm) ? $perm : []),
                '__createdAt' => (string) ($r['created_at'] ?? ''),
                '__updatedAt' => (string) ($r['updated_at'] ?? ''),
            ];
        }, $rows);
    }

    public function staffRoleCreate(int $clientId, array $payload): array
    {
        if ($clientId <= 0 || ! $this->clientExists($clientId)) {
            bad('clientId is required');
        }
        $name = s($payload['name'] ?? '');
        if ($name === '') {
            bad('Role name is required');
        }
        $perm = $this->staffRoleNormPermissions($payload['permissions'] ?? []);
        $code = $this->staffRoleCodeFromName($clientId, $name);
        $ts = now_iso();
        $st = central_db()->prepare('INSERT INTO staff_roles (client_id, code, name, permissions, created_at, updated_at) VALUES (?,?,?,?,?,?)');
        $st->execute([$clientId, $code, $name, json_encode($perm, JSON_UNESCAPED_UNICODE), $ts, $ts]);

        return $this->staffRoleGet($clientId, $code) ?? ['clientId' => $clientId, 'code' => $code, 'name' => $name, 'permissions' => $perm, '__updatedAt' => $ts];
    }

    public function staffRoleUpdate(int $clientId, string $code, array $payload): array
    {
        $row = $this->staffRoleGet($clientId, $code);
        if (! $row) {
            nf('Role not found');
        }
        $name = s($payload['name'] ?? $row['name']);
        if ($name === '') {
            bad('Role name is required');
        }
        $perm = $this->staffRoleNormPermissions($payload['permissions'] ?? $row['permissions']);
        $ts = now_iso();
        $st = central_db()->prepare('UPDATE staff_roles SET name=?, permissions=?, updated_at=? WHERE client_id=? AND code=?');
        $st->execute([$name, json_encode($perm, JSON_UNESCAPED_UNICODE), $ts, $clientId, $row['code']]);

        return $this->staffRoleGet($clientId, $row['code']) ?? ['clientId' => $clientId, 'code' => $row['code'], 'name' => $name, 'permissions' => $perm, '__updatedAt' => $ts];
    }

    public function staffRoleDelete(int $clientId, string $code): void
    {
        $row = $this->staffRoleGet($clientId, $code);
        if (! $row) {
            nf('Role not found');
        }
        $q = central_db()->prepare('SELECT COUNT(*) AS cnt FROM staff_users WHERE client_id=? AND role_code=?');
        $q->execute([$clientId, $row['code']]);
        $cnt = (int) ($q->fetchColumn() ?: 0);
        if ($cnt > 0) {
            j(['detail' => 'Role is assigned to staff users and cannot be deleted'], 409);
        }
        $st = central_db()->prepare('DELETE FROM staff_roles WHERE client_id=? AND code=?');
        $st->execute([$clientId, $row['code']]);
    }

    public function staffRolePermissions(int $clientId, string $code): array
    {
        $row = $this->staffRoleGet($clientId, $code);
        if (! $row) {
            return $this->accessDefaultPermissions();
        }

        return $this->staffRoleNormPermissions($row['permissions'] ?? []);
    }

    public function staffUserRows(int $clientId): array
    {
        if ($clientId <= 0 || ! $this->clientExists($clientId)) {
            return [];
        }
        $emps = employees_all();
        $emap = [];
        foreach ($emps as $e) {
            $eid = up($e['id'] ?? '');
            if ($eid === '') {
                continue;
            }
            $emap[$eid] = $e;
        }
        $q = central_db()->prepare('SELECT id, client_id, emp_id, username, role_code, status, created_at, updated_at FROM staff_users WHERE client_id=? ORDER BY emp_id ASC');
        $q->execute([$clientId]);
        $rows = [];
        foreach ($q->fetchAll() as $r) {
            $eid = up($r['emp_id'] ?? '');
            $e = $emap[$eid] ?? [];
            $rows[] = [
                'id' => (int) $r['id'],
                'clientId' => (int) $r['client_id'],
                'empId' => $eid,
                'empName' => (string) ($e['name'] ?? ''),
                'dept' => (string) ($e['dept'] ?? ''),
                'desig' => (string) ($e['desig'] ?? ''),
                'username' => (string) $r['username'],
                'roleCode' => (string) $r['role_code'],
                'status' => (string) $r['status'],
                '__createdAt' => (string) ($r['created_at'] ?? ''),
                '__updatedAt' => (string) ($r['updated_at'] ?? ''),
            ];
        }

        return $rows;
    }

    public function staffUserGetByUsername(string $username): ?array
    {
        $u = strtolower(trim($username));
        if ($u === '') {
            return null;
        }
        $q = central_db()->prepare('SELECT id, client_id, emp_id, username, password_hash, role_code, status, created_at, updated_at FROM staff_users WHERE lower(username)=? LIMIT 1');
        $q->execute([$u]);
        $r = $q->fetch();
        if (! $r) {
            return null;
        }

        return [
            'id' => (int) $r['id'],
            'clientId' => (int) $r['client_id'],
            'empId' => up($r['emp_id'] ?? ''),
            'username' => (string) $r['username'],
            'passwordHash' => (string) ($r['password_hash'] ?? ''),
            'roleCode' => (string) ($r['role_code'] ?? ''),
            'status' => (string) ($r['status'] ?? 'Active'),
            '__createdAt' => (string) ($r['created_at'] ?? ''),
            '__updatedAt' => (string) ($r['updated_at'] ?? ''),
        ];
    }

    public function staffUserUpsert(int $clientId, string $empId, array $payload): array
    {
        if ($clientId <= 0 || ! $this->clientExists($clientId)) {
            bad('clientId is required');
        }
        $empId = up($empId);
        if ($empId === '') {
            bad('empId is required');
        }
        $existsEmp = false;
        foreach (employees_all() as $e) {
            if (up($e['id'] ?? '') === $empId) {
                $existsEmp = true;
                break;
            }
        }
        if (! $existsEmp) {
            bad('Employee not found');
        }
        $username = strtolower(s($payload['username'] ?? ''));
        if ($username === '') {
            bad('username is required');
        }
        $roleCode = strtolower(s($payload['roleCode'] ?? ''));
        if ($roleCode === '') {
            bad('roleCode is required');
        }
        if (! $this->staffRoleGet($clientId, $roleCode)) {
            bad('Invalid roleCode');
        }
        $status = s($payload['status'] ?? 'Active', 'Active');
        $status = strtolower($status) === 'inactive' ? 'Inactive' : 'Active';
        $password = s($payload['password'] ?? '');
        $ts = now_iso();
        $q = central_db()->prepare('SELECT id, password_hash FROM staff_users WHERE client_id=? AND emp_id=? LIMIT 1');
        $q->execute([$clientId, $empId]);
        $row = $q->fetch();
        $isNew = ! $row;
        if ($row) {
            $id = (int) $row['id'];
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $st = central_db()->prepare('UPDATE staff_users SET username=?, password_hash=?, role_code=?, status=?, updated_at=? WHERE id=?');
                $st->execute([$username, $hash, $roleCode, $status, $ts, $id]);
            } else {
                $st = central_db()->prepare('UPDATE staff_users SET username=?, role_code=?, status=?, updated_at=? WHERE id=?');
                $st->execute([$username, $roleCode, $status, $ts, $id]);
            }
        } else {
            if ($password === '') {
                bad('password is required for new staff user');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = central_db()->prepare('INSERT INTO staff_users (client_id, emp_id, username, password_hash, role_code, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)');
            $st->execute([$clientId, $empId, $username, $hash, $roleCode, $status, $ts, $ts]);
        }
        $list = $this->staffUserRows($clientId);
        foreach ($list as $x) {
            if (up($x['empId'] ?? '') === $empId) {
                mail_staff_access($clientId, (string) ($x['id'] ?? $empId), $x, employee_lookup($empId), $password, $isNew);

                return $x;
            }
        }
        $fallback = ['clientId' => $clientId, 'empId' => $empId, 'username' => $username, 'roleCode' => $roleCode, 'status' => $status, '__updatedAt' => $ts];
        mail_staff_access($clientId, $empId, $fallback, employee_lookup($empId), $password, $isNew);

        return $fallback;
    }

    public function staffUserDelete(int $clientId, string $empId): void
    {
        $empId = up($empId);
        if ($clientId <= 0 || $empId === '') {
            bad('clientId and empId are required');
        }
        $st = central_db()->prepare('DELETE FROM staff_users WHERE client_id=? AND emp_id=?');
        $st->execute([$clientId, $empId]);
        if ($st->rowCount() === 0) {
            nf('Staff user not found');
        }
    }

    private function accessTypeCodeFromName(string $name): string
    {
        $base = strtolower(trim($name));
        $base = preg_replace('/[^a-z0-9]+/', '_', $base ?? '') ?? '';
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'type';
        }
        $code = 'custom_'.$base;
        $i = 1;
        while ($this->accessTypeGet($code) !== null) {
            $i++;
            $code = 'custom_'.$base.'_'.$i;
        }

        return $code;
    }

    private function clientExists(int $id): bool
    {
        $q = central_db()->prepare('SELECT id FROM clients WHERE id=?');
        $q->execute([$id]);

        return (bool) $q->fetch();
    }
}
