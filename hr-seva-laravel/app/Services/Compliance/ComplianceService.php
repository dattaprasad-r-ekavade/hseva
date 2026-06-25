<?php

namespace App\Services\Compliance;

class ComplianceService
{
    public function __construct(private ComplianceRepository $repository) {}

    public function tasks(int $month, int $year): array
    {
        return $this->repository->tasks($month, $year);
    }

    public function saveTasks(int $month, int $year, array $rows): array
    {
        return $this->repository->saveTasks($month, $year, $rows);
    }

    public function resetTasks(int $month, int $year): array
    {
        return $this->repository->resetTasks($month, $year);
    }

    public function clearTasks(): array
    {
        $this->repository->clearTasks();

        return ['status' => 'cleared'];
    }

    public function challans(): array
    {
        return $this->repository->challans();
    }

    public function upsertChallan(array $payload): array
    {
        return $this->repository->upsertChallan($payload);
    }

    public function deleteChallan(string $id): void
    {
        $this->repository->deleteChallan($id);
    }

    public function clearChallans(): array
    {
        $this->repository->clearChallans();

        return ['status' => 'cleared'];
    }
}
