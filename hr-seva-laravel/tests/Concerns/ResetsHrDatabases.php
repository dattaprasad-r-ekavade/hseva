<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

trait ResetsHrDatabases
{
    protected function resetHrDatabases(): void
    {
        if (function_exists('db_reset_pool')) {
            db_reset_pool();
        }

        DB::purge('central');
        DB::purge('tenant');

        $db = storage_path('app/clients/app.db');
        @mkdir(dirname($db), 0777, true);
        if (is_file($db)) {
            @unlink($db);
        }

        foreach (glob(storage_path('app/clients/tenant_*')) ?: [] as $dir) {
            if (is_dir($dir) && is_file($dir.'/app.db')) {
                @unlink($dir.'/app.db');
            }
        }

        $this->artisan('hr:install');
    }
}
