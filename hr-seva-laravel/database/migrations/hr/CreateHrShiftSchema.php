<?php

namespace Database\Migrations\Hr;

use App\Services\Shift\ShiftSchemaInstaller;
use PDO;

class CreateHrShiftSchema
{
    public function up(PDO $pdo): void
    {
        if (function_exists('app') && app()->bound(ShiftSchemaInstaller::class)) {
            app(ShiftSchemaInstaller::class)->install($pdo);

            return;
        }

        (new ShiftSchemaInstaller())->install($pdo);
    }
}
