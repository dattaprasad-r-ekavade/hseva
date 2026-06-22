<?php

namespace App\Services\Leaves;

class LeaveService
{
    public function list(?int $month = null, ?int $year = null, ?string $leaveType = null, ?string $status = null): array
    {
        return leaves_list($month, $year, $leaveType, $status);
    }

    public function upsert(array $body, ?bool $mustExist = null): array
    {
        return leave_upsert($body, $mustExist);
    }

    public function delete(int $id): void
    {
        leave_delete($id);
    }

    public function clear(): array
    {
        db()->exec('DELETE FROM leaves');

        return ['status' => 'cleared'];
    }

    public function bulkUpsert(array $rows): array
    {
        $saved = [];
        foreach ($rows as $row) {
            $saved[] = leave_upsert((array) $row, null);
        }

        return ['rows' => $saved, 'count' => count($saved)];
    }

    public function summary(int $month, int $year): array
    {
        return leaves_summary($month, $year);
    }
}
