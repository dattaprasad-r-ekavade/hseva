<?php

namespace App\Services\Overtime;

class OvertimeService
{
    public function index(): array
    {
        $ctx = overtime_view_ctx();
        $rows = overtime_rows($ctx);

        return ['rows' => $rows, 'stats' => overtime_stats($rows)];
    }

    public function store(array $payload): array
    {
        return ['row' => overtime_create($payload)];
    }

    public function destroy(string $id): array
    {
        overtime_delete($id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        overtime_manage_ctx();
        db()->exec('DELETE FROM overtime_entries');
        invalidate_salary_dependent_sheets();

        return ['status' => 'cleared'];
    }
}
