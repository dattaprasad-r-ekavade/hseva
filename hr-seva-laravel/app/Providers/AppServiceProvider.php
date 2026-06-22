<?php

namespace App\Providers;

use App\Services\Compliance\ComplianceService;
use App\Services\FaceAttendance\FaceAttendanceService;
use App\Services\Payroll\PayrollGenerator;
use App\Services\Payroll\StatutoryCalculator;
use App\Services\Storage\SheetStorageService;
use App\Services\Storage\TenantSettingsService;
use App\Services\Tenant\TenantManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantManager::class);
        $this->app->singleton(TenantSettingsService::class);
        $this->app->singleton(SheetStorageService::class);
        $this->app->singleton(PayrollGenerator::class);
        $this->app->singleton(StatutoryCalculator::class);
        $this->app->singleton(FaceAttendanceService::class);
        $this->app->singleton(ComplianceService::class);
    }

    public function boot(): void
    {
        require_once base_path('legacy/backend/mail.php');
        require_once base_path('legacy/backend/api.php');
    }
}
