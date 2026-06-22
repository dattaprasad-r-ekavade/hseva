<?php

namespace App\Console\Commands;

use App\Services\Auth\AuthService;
use App\Services\Database\SchemaMigrator;
use Illuminate\Console\Command;

class InstallHrSeva extends Command
{
    protected $signature = 'hr:install';

    protected $description = 'Initialize HR Seva central database schema and seed super-admin users';

    public function handle(AuthService $auth, SchemaMigrator $migrator): int
    {
        $migrator->runCentral();
        $auth->seedSuperAdmins();
        $this->info('HR Seva central database initialized.');

        return self::SUCCESS;
    }
}
