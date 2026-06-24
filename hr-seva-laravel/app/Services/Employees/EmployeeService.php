<?php

namespace App\Services\Employees;

class EmployeeService
{
    public function all(bool $activeOnly = false): array
    {
        return $activeOnly ? employees_active_all() : employees_all();
    }

    public function upsert(array $body, ?bool $isUpdate): array
    {
        return emp_upsert($body, $isUpdate);
    }

    public function delete(string $id): array
    {
        emp_delete($id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        db()->exec('DELETE FROM employees');
        invalidate_salary_dependent_sheets();

        return ['status' => 'cleared'];
    }

    public function bulkUpsert(array $rows): array
    {
        $saved = [];
        foreach ($rows as $row) {
            $saved[] = emp_upsert((array) $row, null);
        }

        return ['rows' => $saved, 'count' => count($saved)];
    }
}
