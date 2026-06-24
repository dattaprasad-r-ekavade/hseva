<?php

namespace App\Services\Access;

class AccessService
{
    public function getClientAccess(int $clientId): array
    {
        return ['row' => access_get($clientId)];
    }

    public function putClientAccess(int $clientId, array $payload): array
    {
        return ['row' => access_put($clientId, $payload)];
    }

    public function accessTypes(): array
    {
        return ['rows' => access_type_rows()];
    }

    public function createAccessType(array $payload): array
    {
        return ['row' => access_type_create($payload)];
    }

    public function updateAccessType(string $code, array $payload): array
    {
        return ['row' => access_type_update($code, $payload)];
    }

    public function deleteAccessType(string $code): array
    {
        access_type_delete($code);

        return ['status' => 'deleted'];
    }

    public function staffRoles(int $clientId): array
    {
        return ['rows' => staff_role_rows($clientId)];
    }

    public function createStaffRole(int $clientId, array $payload): array
    {
        return ['row' => staff_role_create($clientId, $payload)];
    }

    public function updateStaffRole(int $clientId, string $code, array $payload): array
    {
        return ['row' => staff_role_update($clientId, $code, $payload)];
    }

    public function deleteStaffRole(int $clientId, string $code): array
    {
        staff_role_delete($clientId, $code);

        return ['status' => 'deleted'];
    }

    public function staffUsers(int $clientId): array
    {
        return ['rows' => staff_user_rows($clientId)];
    }

    public function upsertStaffUser(int $clientId, string $empId, array $payload): array
    {
        return ['row' => staff_user_upsert($clientId, $empId, $payload)];
    }

    public function deleteStaffUser(int $clientId, string $empId): array
    {
        staff_user_delete($clientId, $empId);

        return ['status' => 'deleted'];
    }
}
