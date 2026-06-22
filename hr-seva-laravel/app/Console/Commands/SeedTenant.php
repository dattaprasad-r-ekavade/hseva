<?php

namespace App\Console\Commands;

use App\Services\Tenant\TenantManager;
use App\Support\HrSevaDefaults;
use Illuminate\Console\Command;

class SeedTenant extends Command
{
    protected $signature = 'hr:seed-tenant {clientId}';

    protected $description = 'Seed default tenant settings';

    public function handle(TenantManager $tenants): int
    {
        $clientId = (int) $this->argument('clientId');
        $tenants->setClientId($clientId);
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $db = $tenants->tenant();
        $db->table('tenant_settings')->updateOrInsert(['key' => 'control_settings'], ['value' => json_encode(HrSevaDefaults::CONTROL), 'updated_at' => $now]);
        $db->table('tenant_settings')->updateOrInsert(['key' => 'company_profile'], ['value' => json_encode(HrSevaDefaults::PROFILE), 'updated_at' => $now]);

        return self::SUCCESS;
    }
}
