<?php

namespace Database\Migrations\Hr;

use PDO;

class CreateHrMigrationsTable
{
    public function up(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS hr_migrations (
            version INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            ran_at TEXT NOT NULL
        )');
    }
}
