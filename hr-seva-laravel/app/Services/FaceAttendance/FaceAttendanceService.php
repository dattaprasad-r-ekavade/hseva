<?php

namespace App\Services\FaceAttendance;

class FaceAttendanceService
{
    public function __construct(private FaceAttendanceRepository $repository) {}

    public function settings(): array
    {
        return $this->repository->settingsGet();
    }

    public function updateSettings(array $payload): array
    {
        return $this->repository->settingsPut($payload);
    }

    public function registrations(?string $employeeId = null): array
    {
        return $this->repository->registrationRows($employeeId);
    }

    public function register(array $payload): array
    {
        return $this->repository->register($payload);
    }

    public function deleteRegistration(string $employeeId): void
    {
        $this->repository->deleteRegistration($employeeId);
    }

    public function scan(array $payload): array
    {
        return $this->repository->scan($payload);
    }

    public function sheet(array $query, array $ctx): array
    {
        return $this->repository->sheetRows($query, $ctx);
    }

    public function report(array $query, array $ctx): array
    {
        return $this->repository->reportRows($query, $ctx);
    }

    public function record(int $id): ?array
    {
        return $this->repository->fetchOne($id);
    }

    public function updateRecord(int $id, array $payload): array
    {
        return $this->repository->updateRecord($id, $payload);
    }

    public function deleteRecord(int $id): void
    {
        $this->repository->deleteRecord($id);
    }

    public function viewContext(): array
    {
        return $this->repository->viewContext();
    }

    public function employeeScope(array $ctx): string
    {
        return $this->repository->employeeScope($ctx);
    }
}
