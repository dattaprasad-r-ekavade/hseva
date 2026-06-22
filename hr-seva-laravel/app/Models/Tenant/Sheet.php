<?php

namespace App\Models\Tenant;

use App\Models\TenantModel;

class Sheet extends TenantModel
{
    protected $table = 'sheets';

    protected $primaryKey = null;

    public $incrementing = false;

    protected $guarded = [];
}
