<?php

namespace Database\Migrations\Hr;

use PDO;

class CreateHrNormalizedSchema
{
    public function up(PDO $pdo): void
    {
        hr_init_normalized_schema($pdo);
    }
}
