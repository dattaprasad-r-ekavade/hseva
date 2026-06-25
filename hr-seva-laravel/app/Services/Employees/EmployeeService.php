<?php

namespace App\Services\Employees;

class EmployeeService
{
    public function __construct(private EmployeeRepository $repository) {}

    public function all(bool $activeOnly = false): array
    {
        return $activeOnly ? $this->repository->activeAll() : $this->repository->all();
    }

    public function upsert(array $body, ?bool $isUpdate): array
    {
        return $this->repository->upsert($body, $isUpdate);
    }

    public function delete(string $id): array
    {
        $this->repository->delete($id);

        return ['status' => 'deleted'];
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
}
