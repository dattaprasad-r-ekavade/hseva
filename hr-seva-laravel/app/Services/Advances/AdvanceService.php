<?php

namespace App\Services\Advances;

class AdvanceService
{
    public function index(bool $outstandingOnly = false): array
    {
        $ctx = advance_view_ctx();

        return ['rows' => advance_rows($ctx, $outstandingOnly)];
    }

    public function store(array $payload): array
    {
        return ['row' => advance_create($payload)];
    }

    public function eligibility(string $empId, string $asOfDate): array
    {
        advance_view_ctx();

        return ['row' => advance_eligibility($empId, $asOfDate)];
    }

    public function history(): array
    {
        return ['rows' => advance_history_rows(advance_view_ctx())];
    }

    public function show(string $id): array
    {
        $ctx = advance_view_ctx();
        $row = advance_fetch_one(db(), $id);
        if (! $row) {
            nf('Advance not found');
        }
        $scope = advance_emp_scope($ctx);
        if ($scope !== '' && $scope !== up($row['empId'] ?? '')) {
            j(['detail' => 'Forbidden'], 403);
        }

        return ['row' => $row];
    }

    public function destroy(string $id): array
    {
        advance_delete($id);

        return ['status' => 'deleted'];
    }
}
