<?php

namespace App\Services\Leaves;

class LeaveService
{
    public function __construct(private LeaveRepository $repository) {}

    public function list(?int $month = null, ?int $year = null, ?string $leaveType = null, ?string $status = null): array
    {
        return $this->repository->list($month, $year, $leaveType, $status);
    }

    public function upsert(array $body, ?bool $mustExist = null): array
    {
        return $this->repository->upsert($body, $mustExist);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }

    public function clear(): array
    {
        $this->repository->clear();

        return ['status' => 'cleared'];
    }

    public function bulkUpsert(array $rows): array
    {
        $saved = [];
        foreach ($rows as $row) {
            $saved[] = $this->repository->upsert((array) $row, null);
        }

        return ['rows' => $saved, 'count' => count($saved)];
    }

    public function summary(int $month, int $year): array
    {
        return $this->repository->summary($month, $year);
    }
}
