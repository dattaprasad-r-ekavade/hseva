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
        $this->app->singleton(\App\Services\Overtime\OvertimeService::class);
        $this->app->singleton(\App\Services\Advances\AdvanceService::class);
        $this->app->singleton(\App\Services\Loans\LoanService::class);
        $this->app->singleton(\App\Services\Payroll\PfSheetService::class);
        $this->app->singleton(\App\Services\Payroll\PfReturnService::class);
        $this->app->singleton(\App\Services\Payroll\EsicSheetService::class);
        $this->app->singleton(\App\Services\Payroll\EsicReturnService::class);
        $this->app->singleton(\App\Services\Payroll\EcrSheetService::class);
        $this->app->singleton(\App\Services\Incentives\IncentiveService::class);
        $this->app->singleton(\App\Services\Bonus\BonusService::class);
        $this->app->singleton(\App\Services\Gratuity\GratuityService::class);
        $this->app->singleton(\App\Services\Fnf\FnfService::class);
        $this->app->singleton(\App\Services\Payslips\PayslipService::class);
        $this->app->singleton(\App\Services\Admin\AdminService::class);
        $this->app->singleton(\App\Services\Access\AccessService::class);
        $this->app->singleton(\App\Services\MasterData\MasterDataService::class);
        $this->app->singleton(\App\Services\Billing\BillingService::class);
        $this->app->singleton(\App\Services\PublicEnquiry\PublicEnquiryService::class);
    }

    public function boot(): void
    {
        require_once base_path('legacy/backend/mail.php');
        require_once base_path('legacy/backend/api.php');
    }
}
