<?php

namespace App\Providers;

use App\Services\Auth\AuthService;
use App\Services\Tenant\TenantManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantManager::class);
    }

    public function boot(): void
    {
        require_once base_path('legacy/backend/mail.php');
        require_once base_path('legacy/backend/api.php');
    }
}
