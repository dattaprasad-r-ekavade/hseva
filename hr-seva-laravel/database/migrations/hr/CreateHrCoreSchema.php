<?php

namespace Database\Migrations\Hr;

use PDO;

class CreateHrCoreSchema
{
    public function up(PDO $pdo): void
    {
        init_schema($pdo);
    }
}
