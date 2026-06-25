<?php

namespace App\Services\Overtime;

class OvertimeService
{
    public function __construct(private OvertimeRepository $repository) {}

    public function index(): array
    {
        $ctx = overtime_view_ctx();
        $rows = $this->repository->rows($ctx);

        return ['rows' => $rows, 'stats' => $this->repository->stats($rows)];
    }

    public function store(array $payload): array
    {
        return ['row' => $this->repository->create($payload)];
    }

    public function destroy(string $id): array
    {
        $this->repository->delete($id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        $this->repository->clear();

        return ['status' => 'cleared'];
    }
}
