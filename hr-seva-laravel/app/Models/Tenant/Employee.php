<?php

namespace App\Models\Tenant;

use App\Models\TenantModel;

class Employee extends TenantModel
{
    protected $table = 'employees';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
