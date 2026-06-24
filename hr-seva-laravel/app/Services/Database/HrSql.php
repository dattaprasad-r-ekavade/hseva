<?php

namespace App\Services\Database;

use PDO;

class HrSql
{
    public function __construct(private PDO $pdo) {}

    public function driver(): string
    {
        return (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function isMysql(): bool
    {
        return $this->driver() === 'mysql';
    }

    public function autoIncrementPk(string $name = 'id'): string
    {
        if ($this->isMysql()) {
            return "{$name} BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
        }

        return "{$name} INTEGER PRIMARY KEY AUTOINCREMENT";
    }

    public function realType(): string
    {
        return $this->isMysql() ? 'DOUBLE' : 'REAL';
    }

    public function boolInt(): string
    {
        return $this->isMysql() ? 'TINYINT(1)' : 'INTEGER';
    }

    public function exec(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    public function hasColumn(string $table, string $column): bool
    {
        if ($this->isMysql()) {
            $st = $this->pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $st->execute([$table, $column]);

            return (int) $st->fetchColumn() > 0;
        }

        $cols = $this->pdo->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($cols as $col) {
            if ((string) ($col['name'] ?? '') === $column) {
                return true;
            }
        }

        return false;
    }

    public function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        if ($this->hasColumn($table, $column)) {
            return;
        }
        $this->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    public function insertOrIgnore(string $table, array $columns, array $values): void
    {
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $cols = implode(',', $columns);
        if ($this->isMysql()) {
            $sql = "INSERT IGNORE INTO {$table} ({$cols}) VALUES ({$placeholders})";
        } else {
            $sql = "INSERT OR IGNORE INTO {$table} ({$cols}) VALUES ({$placeholders})";
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($values);
    }
}
