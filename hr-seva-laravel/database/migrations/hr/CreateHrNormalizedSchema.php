<?php

namespace Database\Migrations\Hr;

use App\Services\Database\HrSql;
use PDO;

class CreateHrNormalizedSchema
{
    public function up(PDO $pdo): void
    {
        $sql = new HrSql($pdo);

        $sql->exec('CREATE TABLE IF NOT EXISTS tenant_settings (
            `key` TEXT PRIMARY KEY,
            value TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )');

        $sql->exec('CREATE TABLE IF NOT EXISTS attendance_daily (
            year INTEGER NOT NULL,
            month INTEGER NOT NULL,
            records TEXT NOT NULL,
            PRIMARY KEY (year, month)
        )');

        $sql->exec('CREATE TABLE IF NOT EXISTS payroll_overrides (
            emp_id TEXT PRIMARY KEY,
            data TEXT NOT NULL
        )');

        $sql->exec('CREATE TABLE IF NOT EXISTS sheet_indexes (
            sheet_type TEXT PRIMARY KEY,
            entries TEXT NOT NULL
        )');

        $sql->exec('CREATE TABLE IF NOT EXISTS sheets (
            sheet_type TEXT NOT NULL,
            sheet_id TEXT NOT NULL,
            month INTEGER NOT NULL,
            year INTEGER NOT NULL,
            period TEXT NOT NULL,
            data TEXT NOT NULL,
            meta TEXT NOT NULL,
            PRIMARY KEY (sheet_type, sheet_id)
        )');

        $sql->exec('CREATE TABLE IF NOT EXISTS challans (
            challan_type TEXT NOT NULL,
            challan_id TEXT NOT NULL,
            data TEXT NOT NULL,
            PRIMARY KEY (challan_type, challan_id)
        )');
    }
}
