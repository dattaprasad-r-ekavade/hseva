<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT / Auth Secret
    |--------------------------------------------------------------------------
    |
    | Must match the original HR_APP_SECRET used by the legacy API for token
    | compatibility with existing frontend sessions.
    |
    */

    'app_secret' => env('HR_APP_SECRET', 'change-this-secret-before-production'),

    /*
    |--------------------------------------------------------------------------
    | Client / Tenant Storage
    |--------------------------------------------------------------------------
    |
    | SQLite databases for central and per-tenant data. Switch to MySQL/Postgres
    | by refactoring legacy/backend/db_open() and adding Laravel migrations.
    |
    */

    'storage_dir' => storage_path('app/clients'),

    'central_db_path' => storage_path('app/clients/app.db'),

    'auth_token_ttl' => 43200, // 12 hours

];
