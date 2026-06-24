<?php

namespace App\Services\Access;

class AccessService
{
    public function __construct(private AccessRepository $repository) {}

    public function getClientAccess(int $clientId): array
    {
        return ['row' => $this->repository->accessGet($clientId)];
    }

    public function putClientAccess(int $clientId, array $payload): array
    {
        return ['row' => $this->repository->accessPut($clientId, $payload)];
    }

    public function accessTypes(): array
    {
        return ['rows' => $this->repository->accessTypeRows()];
    }

    public function createAccessType(array $payload): array
    {
        return ['row' => $this->repository->accessTypeCreate($payload)];
    }

    public function updateAccessType(string $code, array $payload): array
    {
        return ['row' => $this->repository->accessTypeUpdate($code, $payload)];
    }

    public function deleteAccessType(string $code): array
    {
        $this->repository->accessTypeDelete($code);

        return ['status' => 'deleted'];
    }

    public function staffRoles(int $clientId): array
    {
        return ['rows' => $this->repository->staffRoleRows($clientId)];
    }

    public function createStaffRole(int $clientId, array $payload): array
    {
        return ['row' => $this->repository->staffRoleCreate($clientId, $payload)];
    }

    public function updateStaffRole(int $clientId, string $code, array $payload): array
    {
        return ['row' => $this->repository->staffRoleUpdate($clientId, $code, $payload)];
    }

    public function deleteStaffRole(int $clientId, string $code): array
    {
        $this->repository->staffRoleDelete($clientId, $code);

        return ['status' => 'deleted'];
    }

    public function staffUsers(int $clientId): array
    {
        return ['rows' => $this->repository->staffUserRows($clientId)];
    }

    public function upsertStaffUser(int $clientId, string $empId, array $payload): array
    {
        return ['row' => $this->repository->staffUserUpsert($clientId, $empId, $payload)];
    }

    public function deleteStaffUser(int $clientId, string $empId): array
    {
        $this->repository->staffUserDelete($clientId, $empId);

        return ['status' => 'deleted'];
    }
}
