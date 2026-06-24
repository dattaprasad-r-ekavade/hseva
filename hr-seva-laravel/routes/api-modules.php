<?php

use App\Http\Controllers\Api\AccessController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdvanceController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\BonusController;
use App\Http\Controllers\Api\EcrSheetController;
use App\Http\Controllers\Api\EsicReturnController;
use App\Http\Controllers\Api\EsicSheetController;
use App\Http\Controllers\Api\FnfController;
use App\Http\Controllers\Api\GratuityController;
use App\Http\Controllers\Api\IncentiveController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\OvertimeController;
use App\Http\Controllers\Api\PayslipController;
use App\Http\Controllers\Api\PfReturnController;
use App\Http\Controllers\Api\PfSheetController;
use App\Http\Controllers\Api\ShiftController;
use Illuminate\Support\Facades\Route;

Route::get('/overtime', [OvertimeController::class, 'index']);
Route::post('/overtime', [OvertimeController::class, 'store']);
Route::post('/overtime/clear', [OvertimeController::class, 'clear']);
Route::delete('/overtime/{id}', [OvertimeController::class, 'destroy']);

Route::get('/advances', [AdvanceController::class, 'index']);
Route::post('/advances', [AdvanceController::class, 'store']);
Route::get('/advances/eligibility', [AdvanceController::class, 'eligibility']);
Route::get('/advances/history', [AdvanceController::class, 'history']);
Route::get('/advances/{id}', [AdvanceController::class, 'show']);
Route::delete('/advances/{id}', [AdvanceController::class, 'destroy']);

Route::get('/loans', [LoanController::class, 'index']);
Route::post('/loans', [LoanController::class, 'store']);
Route::get('/loans/{loanId}', [LoanController::class, 'show']);
Route::put('/loans/{loanId}', [LoanController::class, 'update']);
Route::delete('/loans/{loanId}', [LoanController::class, 'destroy']);

Route::post('/pf-sheet/generate', [PfSheetController::class, 'generate']);
Route::get('/pf-sheet/sheets', [PfSheetController::class, 'sheets']);
Route::get('/pf-sheet/sheets/{id}', [PfSheetController::class, 'show']);
Route::delete('/pf-sheet/sheets/{id}', [PfSheetController::class, 'destroy']);
Route::post('/pf-sheet/clear', [PfSheetController::class, 'clear']);

Route::post('/pf-return/generate', [PfReturnController::class, 'generate']);
Route::get('/pf-return/sheets', [PfReturnController::class, 'sheets']);
Route::get('/pf-return/sheets/{id}', [PfReturnController::class, 'show']);
Route::delete('/pf-return/sheets/{id}', [PfReturnController::class, 'destroy']);
Route::post('/pf-return/clear', [PfReturnController::class, 'clear']);
Route::get('/pf-return/challans', [PfReturnController::class, 'challans']);
Route::post('/pf-return/challans', [PfReturnController::class, 'storeChallan']);
Route::delete('/pf-return/challans/{id}', [PfReturnController::class, 'destroyChallan']);
Route::post('/pf-return/challans/clear', [PfReturnController::class, 'clearChallans']);

Route::post('/esic-sheet/generate', [EsicSheetController::class, 'generate']);
Route::get('/esic-sheet/sheets', [EsicSheetController::class, 'sheets']);
Route::get('/esic-sheet/sheets/{id}', [EsicSheetController::class, 'show']);
Route::delete('/esic-sheet/sheets/{id}', [EsicSheetController::class, 'destroy']);
Route::post('/esic-sheet/clear', [EsicSheetController::class, 'clear']);

Route::post('/esic-return/generate', [EsicReturnController::class, 'generate']);
Route::get('/esic-return/sheets', [EsicReturnController::class, 'sheets']);
Route::get('/esic-return/sheets/{id}', [EsicReturnController::class, 'show']);
Route::delete('/esic-return/sheets/{id}', [EsicReturnController::class, 'destroy']);
Route::post('/esic-return/clear', [EsicReturnController::class, 'clear']);
Route::get('/esic-return/challans', [EsicReturnController::class, 'challans']);
Route::post('/esic-return/challans', [EsicReturnController::class, 'storeChallan']);
Route::delete('/esic-return/challans/{id}', [EsicReturnController::class, 'destroyChallan']);
Route::post('/esic-return/challans/clear', [EsicReturnController::class, 'clearChallans']);

Route::post('/ecr-sheet/generate', [EcrSheetController::class, 'generate']);
Route::get('/ecr-sheet/sheets', [EcrSheetController::class, 'sheets']);
Route::get('/ecr-sheet/sheets/{id}', [EcrSheetController::class, 'show']);
Route::delete('/ecr-sheet/sheets/{id}', [EcrSheetController::class, 'destroy']);
Route::post('/ecr-sheet/clear', [EcrSheetController::class, 'clear']);

Route::post('/fnf/generate', [FnfController::class, 'generate']);
Route::get('/fnf/sheets', [FnfController::class, 'sheets']);
Route::get('/fnf/sheets/{id}', [FnfController::class, 'show']);
Route::delete('/fnf/sheets/{id}', [FnfController::class, 'destroy']);
Route::post('/fnf/clear', [FnfController::class, 'clear']);

Route::post('/gratuity/generate', [GratuityController::class, 'generate']);
Route::get('/gratuity/sheets', [GratuityController::class, 'sheets']);
Route::get('/gratuity/sheets/{id}', [GratuityController::class, 'show']);
Route::delete('/gratuity/sheets/{id}', [GratuityController::class, 'destroy']);
Route::post('/gratuity/clear', [GratuityController::class, 'clear']);

Route::post('/bonus/generate', [BonusController::class, 'generate']);
Route::match(['GET', 'POST'], '/bonus/sheets', [BonusController::class, 'sheets']);
Route::get('/bonus/sheets/{id}', [BonusController::class, 'show']);
Route::delete('/bonus/sheets/{id}', [BonusController::class, 'destroy']);
Route::post('/bonus/clear', [BonusController::class, 'clear']);

Route::get('/incentives', [IncentiveController::class, 'index']);
Route::post('/incentives', [IncentiveController::class, 'store']);
Route::post('/incentives/clear', [IncentiveController::class, 'clear']);
Route::get('/incentives/{id}', [IncentiveController::class, 'show']);
Route::delete('/incentives/{id}', [IncentiveController::class, 'destroy']);

Route::post('/payslips/generate', [PayslipController::class, 'generate']);
Route::get('/payslips', [PayslipController::class, 'index']);
Route::get('/payslips/{id}', [PayslipController::class, 'show'])->where('id', '^(?!clear$).+');
Route::delete('/payslips/{id}', [PayslipController::class, 'destroy'])->where('id', '^(?!clear$).+');
Route::post('/payslips/clear', [PayslipController::class, 'clear']);

Route::get('/client-access-template', [BillingController::class, 'accessTemplate']);
Route::get('/client-billing', [BillingController::class, 'billing']);
Route::get('/client-invoices', [BillingController::class, 'invoices']);

Route::get('/attendance-statuses', [MasterDataController::class, 'attendanceStatuses']);
Route::post('/attendance-statuses', [MasterDataController::class, 'attendanceStatuses'])->middleware('hr.super_admin');
Route::put('/attendance-statuses/{code}', [MasterDataController::class, 'updateAttendanceStatus'])->middleware('hr.super_admin');
Route::delete('/attendance-statuses/{code}', [MasterDataController::class, 'destroyAttendanceStatus'])->middleware('hr.super_admin');

Route::get('/employee-types', [MasterDataController::class, 'employeeTypes']);
Route::post('/employee-types', [MasterDataController::class, 'employeeTypes'])->middleware('hr.super_admin');
Route::put('/employee-types/{code}', [MasterDataController::class, 'updateEmployeeType'])->middleware('hr.super_admin');
Route::delete('/employee-types/{code}', [MasterDataController::class, 'destroyEmployeeType'])->middleware('hr.super_admin');

Route::get('/access-control/{id}', [AccessController::class, 'showClientAccess'])->middleware('hr.super_admin');
Route::put('/access-control/{id}', [AccessController::class, 'updateClientAccess'])->middleware('hr.super_admin');

Route::get('/access-types', [AccessController::class, 'accessTypes'])->middleware('hr.super_admin');
Route::post('/access-types', [AccessController::class, 'accessTypes'])->middleware('hr.super_admin');
Route::put('/access-types/{code}', [AccessController::class, 'updateAccessType'])->middleware('hr.super_admin');
Route::delete('/access-types/{code}', [AccessController::class, 'destroyAccessType'])->middleware('hr.super_admin');

Route::get('/staff-roles', [AccessController::class, 'staffRoles']);
Route::post('/staff-roles', [AccessController::class, 'staffRoles']);
Route::put('/staff-roles/{code}', [AccessController::class, 'updateStaffRole']);
Route::delete('/staff-roles/{code}', [AccessController::class, 'destroyStaffRole']);

Route::get('/staff-users', [AccessController::class, 'staffUsers']);
Route::put('/staff-users/{empId}', [AccessController::class, 'upsertStaffUser']);
Route::delete('/staff-users/{empId}', [AccessController::class, 'destroyStaffUser']);

Route::middleware('hr.super_admin')->group(function (): void {
    Route::match(['GET', 'POST'], '/admin-enquiries', [AdminController::class, 'enquiries']);
    Route::put('/admin-enquiries/{id}', [AdminController::class, 'updateEnquiry']);
    Route::delete('/admin-enquiries/{id}', [AdminController::class, 'destroyEnquiry']);
    Route::match(['GET', 'PUT'], '/admin-smtp-settings', [AdminController::class, 'smtpSettings']);
    Route::post('/admin-smtp-settings/test', [AdminController::class, 'testSmtp']);
});

Route::get('/shift/dashboard', [ShiftController::class, 'dashboard']);
Route::match(['GET', 'POST'], '/shifts', [ShiftController::class, 'shifts']);
Route::match(['PUT', 'DELETE'], '/shifts/{id}', [ShiftController::class, 'shiftById']);
Route::match(['GET', 'POST'], '/shift-assignments', [ShiftController::class, 'assignments']);
Route::match(['PUT', 'DELETE'], '/shift-assignments/{id}', [ShiftController::class, 'assignmentById']);
Route::get('/rosters', [ShiftController::class, 'rosters']);
Route::post('/rosters/{action}', [ShiftController::class, 'rosterAction'])
    ->where('action', 'delete-cell|bulk-delete|bulk-upsert|auto-fill-week|copy-previous-week');
Route::match(['GET', 'POST'], '/rosters/week-status', [ShiftController::class, 'weekStatus']);
Route::get('/shift-calendar/events', [ShiftController::class, 'calendarEvents']);
Route::get('/roster-attendance-report', [ShiftController::class, 'attendanceReport']);
Route::get('/my-shifts', [ShiftController::class, 'myShifts']);
