<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ComplianceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FaceAttendanceController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PublicEnquiryController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TenantSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot', [AuthController::class, 'forgot']);
Route::get('/auth/session', [AuthController::class, 'session']);

Route::post('/public-enquiries', [PublicEnquiryController::class, 'store']);

Route::middleware(['hr.auth'])->group(function (): void {
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    Route::get('/clients', [ClientController::class, 'index'])->middleware('hr.super_admin');
    Route::post('/clients', [ClientController::class, 'store'])->middleware('hr.super_admin');
    Route::post('/clients/clear', [ClientController::class, 'clear'])->middleware('hr.super_admin');
    Route::put('/clients/{id}', [ClientController::class, 'update'])->middleware('hr.super_admin');
    Route::delete('/clients/{id}', [ClientController::class, 'destroy'])->middleware('hr.super_admin');

    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::post('/employees/clear', [EmployeeController::class, 'clear']);
    Route::post('/employees/bulk-upsert', [EmployeeController::class, 'bulkUpsert']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);

    Route::get('/leaves', [LeaveController::class, 'index']);
    Route::post('/leaves', [LeaveController::class, 'store']);
    Route::post('/leaves/clear', [LeaveController::class, 'clear']);
    Route::post('/leaves/bulk-upsert', [LeaveController::class, 'bulkUpsert']);
    Route::get('/leaves/summary', [LeaveController::class, 'summary']);
    Route::put('/leaves/{id}', [LeaveController::class, 'update']);
    Route::delete('/leaves/{id}', [LeaveController::class, 'destroy']);

    Route::get('/attendance/daily', [AttendanceController::class, 'daily']);
    Route::post('/attendance/daily/upsert', [AttendanceController::class, 'dailyUpsert']);
    Route::post('/attendance/generate', [AttendanceController::class, 'generate']);
    Route::get('/attendance/sheets', [AttendanceController::class, 'sheets']);
    Route::get('/attendance/sheets/{id}', [AttendanceController::class, 'showSheet']);
    Route::delete('/attendance/sheets/{id}', [AttendanceController::class, 'destroySheet']);
    Route::post('/attendance/sheets/clear', [AttendanceController::class, 'clearSheets']);
    Route::post('/attendance/clear', [AttendanceController::class, 'clear']);

    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->middleware('hr.super_admin');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->middleware('hr.super_admin');
    Route::put('/subscriptions/{id}', [SubscriptionController::class, 'update'])->middleware('hr.super_admin');
    Route::delete('/subscriptions/{id}', [SubscriptionController::class, 'destroy'])->middleware('hr.super_admin');
    Route::get('/subscription-plans', [SubscriptionController::class, 'plans'])->middleware('hr.super_admin');
    Route::post('/subscription-plans', [SubscriptionController::class, 'storePlan'])->middleware('hr.super_admin');
    Route::put('/subscription-plans/{id}', [SubscriptionController::class, 'updatePlan'])->middleware('hr.super_admin');
    Route::delete('/subscription-plans/{id}', [SubscriptionController::class, 'destroyPlan'])->middleware('hr.super_admin');
    Route::get('/subscription-info', [SubscriptionController::class, 'info']);

    Route::match(['GET', 'PUT'], '/control', [TenantSettingsController::class, 'control']);
    Route::post('/control/reset', [TenantSettingsController::class, 'resetControl']);
    Route::match(['GET', 'PUT'], '/profile', [TenantSettingsController::class, 'profile']);
    Route::post('/profile/reset', [TenantSettingsController::class, 'resetProfile']);

    Route::post('/payroll/generate', [PayrollController::class, 'generate']);
    Route::get('/payroll/sheets', [PayrollController::class, 'sheets']);
    Route::get('/payroll/sheets/{id}', [PayrollController::class, 'show']);
    Route::delete('/payroll/sheets/{id}', [PayrollController::class, 'destroy']);
    Route::post('/payroll/clear', [PayrollController::class, 'clear']);
    Route::get('/payroll/overrides', [PayrollController::class, 'overrides']);
    Route::put('/payroll/overrides/{empId}', [PayrollController::class, 'setOverride']);
    Route::delete('/payroll/overrides/{empId}', [PayrollController::class, 'deleteOverride']);

    Route::match(['GET', 'PUT'], '/face-attendance/settings', [FaceAttendanceController::class, 'settings']);
    Route::get('/face-attendance/registrations', [FaceAttendanceController::class, 'registrations']);
    Route::post('/face-attendance/register', [FaceAttendanceController::class, 'register']);
    Route::delete('/face-attendance/registrations/{employeeId}', [FaceAttendanceController::class, 'destroyRegistration']);
    Route::post('/face-attendance/scan', [FaceAttendanceController::class, 'scan']);
    Route::get('/face-attendance/sheet', [FaceAttendanceController::class, 'sheet']);
    Route::get('/face-attendance/report', [FaceAttendanceController::class, 'report']);
    Route::get('/face-attendance/my-attendance', [FaceAttendanceController::class, 'myAttendance']);
    Route::get('/face-attendance/attendance/{id}', [FaceAttendanceController::class, 'showAttendance']);
    Route::put('/face-attendance/attendance/{id}', [FaceAttendanceController::class, 'updateAttendance']);
    Route::delete('/face-attendance/attendance/{id}', [FaceAttendanceController::class, 'destroyAttendance']);

    Route::get('/compliance/tasks', [ComplianceController::class, 'tasks']);
    Route::post('/compliance/tasks/upsert', [ComplianceController::class, 'upsertTasks']);
    Route::post('/compliance/tasks/reset', [ComplianceController::class, 'resetTasks']);
    Route::post('/compliance/tasks/clear', [ComplianceController::class, 'clearTasks']);
    Route::get('/compliance/challans', [ComplianceController::class, 'challans']);
    Route::post('/compliance/challans', [ComplianceController::class, 'storeChallan']);
    Route::delete('/compliance/challans/{id}', [ComplianceController::class, 'destroyChallan']);
    Route::post('/compliance/challans/clear', [ComplianceController::class, 'clearChallans']);

    require __DIR__.'/api-modules.php';
});
