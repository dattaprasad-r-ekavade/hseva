<?php

return [

    'app_secret' => env('HR_APP_SECRET', 'change-this-secret-before-production'),

    'storage_dir' => storage_path('app/clients'),

    'central_db_path' => storage_path('app/clients/app.db'),

    'auth_token_ttl' => 43200,

    /*
    |--------------------------------------------------------------------------
    | Database drivers: sqlite | mysql
    |--------------------------------------------------------------------------
    |
    | SQLite (default): one file per tenant under storage/app/clients/
    | MySQL: central DB + per-tenant database (hr_seva_tenant_{id})
    |
    */

    'central_driver' => env('HR_CENTRAL_DRIVER', 'sqlite'),

    'tenant_driver' => env('HR_TENANT_DRIVER', 'sqlite'),

    'mysql' => [
        'host' => env('HR_MYSQL_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('HR_MYSQL_PORT', env('DB_PORT', '3306')),
        'username' => env('HR_MYSQL_USERNAME', env('DB_USERNAME', 'root')),
        'password' => env('HR_MYSQL_PASSWORD', env('DB_PASSWORD', '')),
        'central_database' => env('HR_MYSQL_CENTRAL_DB', 'hr_seva_central'),
        'tenant_database_prefix' => env('HR_MYSQL_TENANT_DB_PREFIX', 'hr_seva_tenant_'),
    ],

];
