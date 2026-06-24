<?php

namespace App\Services\Compliance;

class ComplianceService
{
    public function tasks(int $month, int $year): array
    {
        return compliance_list($month, $year);
    }

    public function saveTasks(int $month, int $year, array $rows): array
    {
        return compliance_save($month, $year, $rows);
    }

    public function resetTasks(int $month, int $year): array
    {
        return compliance_reset($month, $year);
    }

    public function clearTasks(): array
    {
        db()->exec("DELETE FROM app_kv WHERE key LIKE 'compliance_%'");

        return ['status' => 'cleared'];
    }

    public function challans(): array
    {
        return compliance_challan_list();
    }

    public function upsertChallan(array $payload): array
    {
        return compliance_challan_upsert($payload);
    }

    public function deleteChallan(string $id): void
    {
        compliance_challan_delete($id);
    }

    public function clearChallans(): array
    {
        compliance_challan_clear();

        return ['status' => 'cleared'];
    }
}
