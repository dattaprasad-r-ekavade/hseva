<?php

namespace App\Services\Incentives;

class IncentiveService
{
    public function index(): array
    {
        return ['rows' => incentive_rows()];
    }

    public function store(array $payload): array
    {
        return ['row' => incentive_create($payload)];
    }

    public function show(string $id): array
    {
        $row = incentive_fetch_one($id);
        if (! $row) {
            nf('Incentive not found');
        }

        return ['row' => $row];
    }

    public function destroy(string $id): array
    {
        incentive_delete($id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        incentive_clear();

        return ['status' => 'cleared'];
    }
}
