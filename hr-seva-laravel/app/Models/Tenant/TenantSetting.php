<?php

namespace App\Models\Tenant;

use App\Models\TenantModel;

class TenantSetting extends TenantModel
{
    protected $table = 'tenant_settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
