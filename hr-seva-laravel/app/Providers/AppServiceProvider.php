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
        $this->app->singleton(\App\Services\Attendance\AttendanceGenerator::class);
        $this->app->singleton(\App\Services\Payroll\PayrollSheetResolver::class);
        $this->app->singleton(\App\Services\Payroll\PfSheetGenerator::class);
        $this->app->singleton(\App\Services\Payroll\PfReturnGenerator::class);
        $this->app->singleton(\App\Services\Payroll\EsicSheetGenerator::class);
        $this->app->singleton(\App\Services\Payroll\EcrSheetGenerator::class);
        $this->app->singleton(\App\Services\Payroll\EsicReturnGenerator::class);
        $this->app->singleton(\App\Services\Sheets\SheetCrudService::class);
        $this->app->singleton(\App\Services\Fnf\FnfGenerator::class);
        $this->app->singleton(\App\Services\Gratuity\GratuityGenerator::class);
        $this->app->singleton(\App\Services\Bonus\BonusGenerator::class);
        $this->app->singleton(\App\Services\Payslips\PayslipGenerator::class);
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
        $this->app->singleton(\App\Services\Shift\ShiftSupport::class);
        $this->app->singleton(\App\Services\Shift\ShiftAccess::class);
        $this->app->singleton(\App\Services\Shift\ShiftSchemaInstaller::class);
        $this->app->singleton(\App\Services\Shift\ShiftMasterRepository::class);
        $this->app->singleton(\App\Services\Shift\ShiftAssignmentRepository::class);
        $this->app->singleton(\App\Services\Shift\ShiftRosterRepository::class);
        $this->app->singleton(\App\Services\Shift\ShiftCalendarService::class);
        $this->app->singleton(\App\Services\Shift\ShiftReportService::class);
        $this->app->singleton(\App\Services\Shift\ShiftDashboardService::class);
        $this->app->singleton(\App\Services\Shift\ShiftService::class);
    }

    public function boot(): void
    {
        require_once base_path('legacy/backend/mail.php');
        require_once base_path('legacy/backend/api.php');
    }
}
