<?php

namespace App\Services\MasterData;

class MasterDataService
{
    public function attendanceStatuses(bool $activeOnly = false): array
    {
        return ['rows' => attendance_status_rows($activeOnly)];
    }

    public function upsertAttendanceStatus(array $payload, bool $isUpdate = false): array
    {
        return ['row' => attendance_status_upsert($payload, $isUpdate)];
    }

    public function deleteAttendanceStatus(string $code): array
    {
        attendance_status_delete($code);

        return ['status' => 'deleted'];
    }

    public function employeeTypes(bool $activeOnly = false): array
    {
        return ['rows' => employee_type_rows($activeOnly)];
    }

    public function upsertEmployeeType(array $payload, bool $isUpdate = false): array
    {
        return ['row' => employee_type_upsert($payload, $isUpdate)];
    }

    public function deleteEmployeeType(string $code): array
    {
        employee_type_delete($code);

        return ['status' => 'deleted'];
    }
}
