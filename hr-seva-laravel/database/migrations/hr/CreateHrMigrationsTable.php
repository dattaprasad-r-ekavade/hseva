<?php

namespace Database\Migrations\Hr;

use App\Services\Database\HrSql;
use PDO;

class CreateHrMigrationsTable
{
    public function up(PDO $pdo): void
    {
        $sql = new HrSql($pdo);
        $sql->exec('CREATE TABLE IF NOT EXISTS hr_migrations (
            version INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            ran_at TEXT NOT NULL
        )');
    }
}
