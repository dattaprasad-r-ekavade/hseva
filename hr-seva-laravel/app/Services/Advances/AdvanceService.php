<?php

namespace App\Services\Advances;

class AdvanceService
{
    public function __construct(private AdvanceRepository $repository) {}

    public function index(bool $outstandingOnly = false): array
    {
        $ctx = advance_view_ctx();

        return ['rows' => $this->repository->rows($ctx, $outstandingOnly)];
    }

    public function store(array $payload): array
    {
        return ['row' => $this->repository->create($payload)];
    }

    public function eligibility(string $empId, string $asOfDate): array
    {
        advance_view_ctx();

        return ['row' => $this->repository->eligibility($empId, $asOfDate)];
    }

    public function history(): array
    {
        return ['rows' => $this->repository->historyRows(advance_view_ctx())];
    }

    public function show(string $id): array
    {
        $ctx = advance_view_ctx();
        $row = $this->repository->fetchOne(db(), $id);
        if (! $row) {
            nf('Advance not found');
        }
        $scope = $this->repository->employeeScope($ctx);
        if ($scope !== '' && $scope !== up($row['empId'] ?? '')) {
            j(['detail' => 'Forbidden'], 403);
        }

        return ['row' => $row];
    }

    public function destroy(string $id): array
    {
        $this->repository->delete($id);

        return ['status' => 'deleted'];
    }
}
