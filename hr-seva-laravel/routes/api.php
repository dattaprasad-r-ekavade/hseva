<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\HrSevaApiController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\TenantSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot', [AuthController::class, 'forgot']);
Route::get('/auth/session', [AuthController::class, 'session']);

Route::post('/public-enquiries', HrSevaApiController::class);

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

    Route::any('/{path?}', HrSevaApiController::class)->where('path', '.*');
});
