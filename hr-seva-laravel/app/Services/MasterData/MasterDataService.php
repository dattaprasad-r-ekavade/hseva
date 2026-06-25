<?php

namespace App\Services\MasterData;

class MasterDataService
{
    public function __construct(private MasterDataRepository $repository) {}

    public function attendanceStatuses(bool $activeOnly = false): array
    {
        return ['rows' => $this->repository->attendanceStatuses($activeOnly)];
    }

    public function upsertAttendanceStatus(array $payload, bool $isUpdate = false): array
    {
        return ['row' => $this->repository->upsertAttendanceStatus($payload, $isUpdate)];
    }

    public function deleteAttendanceStatus(string $code): array
    {
        $this->repository->deleteAttendanceStatus($code);

        return ['status' => 'deleted'];
    }

    public function employeeTypes(bool $activeOnly = false): array
    {
        return ['rows' => $this->repository->employeeTypes($activeOnly)];
    }

    public function upsertEmployeeType(array $payload, bool $isUpdate = false): array
    {
        return ['row' => $this->repository->upsertEmployeeType($payload, $isUpdate)];
    }

    public function deleteEmployeeType(string $code): array
    {
        $this->repository->deleteEmployeeType($code);

        return ['status' => 'deleted'];
    }
}
