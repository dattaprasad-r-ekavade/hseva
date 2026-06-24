<?php

namespace App\Services\FaceAttendance;

class FaceAttendanceService
{
    public function settings(): array
    {
        return face_attendance_settings_get();
    }

    public function updateSettings(array $payload): array
    {
        return face_attendance_settings_put($payload);
    }

    public function registrations(?string $employeeId = null): array
    {
        return face_attendance_registration_rows($employeeId);
    }

    public function register(array $payload): array
    {
        return face_attendance_register($payload);
    }

    public function deleteRegistration(string $employeeId): void
    {
        face_attendance_delete_registration($employeeId);
    }

    public function scan(array $payload): array
    {
        return face_attendance_scan($payload);
    }

    public function sheet(array $query, array $ctx): array
    {
        return face_attendance_sheet_rows($query, $ctx);
    }

    public function report(array $query, array $ctx): array
    {
        return face_attendance_report_rows($query, $ctx);
    }

    public function record(int $id): ?array
    {
        return face_attendance_fetch_one($id);
    }

    public function updateRecord(int $id, array $payload): array
    {
        return face_attendance_update_record($id, $payload);
    }

    public function deleteRecord(int $id): void
    {
        face_attendance_delete_record($id);
    }

    public function viewContext(): array
    {
        return face_attendance_view_ctx();
    }

    public function employeeScope(array $ctx): string
    {
        return face_attendance_emp_scope($ctx);
    }
}
