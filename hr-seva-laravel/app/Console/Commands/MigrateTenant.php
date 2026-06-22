<?php

namespace App\Console\Commands;

use App\Services\Database\SchemaMigrator;
use App\Services\Tenant\TenantManager;
use Illuminate\Console\Command;

class MigrateTenant extends Command
{
    protected $signature = 'hr:migrate-tenant {clientId}';

    protected $description = 'Initialize tenant database schema for a client';

    public function handle(TenantManager $tenants, SchemaMigrator $migrator): int
    {
        $clientId = (int) $this->argument('clientId');
        $migrator->runTenant($clientId);
        $this->info("Tenant {$clientId} database migrated.");

        return self::SUCCESS;
    }
}
