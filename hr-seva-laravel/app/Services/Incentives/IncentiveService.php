<?php

namespace App\Services\Incentives;

class IncentiveService
{
    public function __construct(private IncentiveRepository $repository) {}

    public function index(): array
    {
        return ['rows' => $this->repository->all()];
    }

    public function store(array $payload): array
    {
        return ['row' => $this->repository->create($payload)];
    }

    public function show(string $id): array
    {
        $row = $this->repository->find($id);
        if (! $row) {
            nf('Incentive not found');
        }

        return ['row' => $row];
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
