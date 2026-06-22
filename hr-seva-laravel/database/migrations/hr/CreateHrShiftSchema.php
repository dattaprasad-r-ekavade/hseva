<?php

namespace Database\Migrations\Hr;

use App\Services\Database\HrSql;
use PDO;

class CreateHrShiftSchema
{
    public function up(PDO $pdo): void
    {
        require_once dirname(__DIR__, 3).'/legacy/backend/shift_module.php';
        init_shift_schema($pdo);
    }
}
