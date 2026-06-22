<?php

namespace App\Models\Tenant;

use App\Models\TenantModel;

class PayrollOverride extends TenantModel
{
    protected $table = 'payroll_overrides';

    protected $primaryKey = 'emp_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
