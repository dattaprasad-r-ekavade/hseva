<?php

namespace App\Support;

class NavigationBuilder
{
    public static function sections(string $portal): array
    {
        $isSuper = $portal === 'super-admin';
        $prefix = $isSuper ? 'super-admin' : 'client';
        $dash = $isSuper ? '/super-admin' : '/client';
        $p = fn (string $slug) => url($dash.'/'.ltrim($slug, '/'));

        $main = $isSuper
            ? [
                ['label' => 'Super Dashboard', 'icon' => 'bi-speedometer2', 'href' => $p('super-admin-dashboard.html'), 'permission' => 'dashboard', 'page' => 'super-admin-dashboard.html'],
                ['label' => 'Client Dashboard', 'icon' => 'bi-grid', 'href' => $p('index.html'), 'permission' => 'dashboard', 'page' => 'index.html'],
            ]
            : [
                ['label' => 'Dashboard', 'icon' => 'bi-grid', 'href' => $p('index.html'), 'permission' => 'dashboard', 'page' => 'index.html'],
            ];

        return [
            ['title' => 'Main', 'items' => array_merge($main, [
                ['label' => 'Client Module', 'icon' => 'bi-building', 'href' => $p($prefix.'-module.html'), 'permission' => 'clientModule', 'page' => $prefix.'-module.html'],
                ['label' => 'Shift / Roster', 'icon' => 'bi-calendar-week', 'href' => $p($prefix.'-shift-roster.html'), 'permission' => 'shiftRoster', 'page' => $prefix.'-shift-roster.html'],
                ['label' => 'Employee Master', 'icon' => 'bi-people', 'href' => $p($prefix.'-employee-master.html'), 'permission' => 'employeeMaster', 'page' => $prefix.'-employee-master.html'],
                ['label' => 'Employee Type', 'icon' => 'bi-person-vcard', 'href' => $p($prefix.'-employee-types.html'), 'permission' => 'employeeType', 'page' => $prefix.'-employee-types.html'],
            ])],
            ['title' => 'Statutory', 'items' => [
                ['label' => 'Salary Sheet', 'icon' => 'bi-calculator', 'href' => $p($prefix.'-payroll-calc.html'), 'permission' => 'salarySheet', 'page' => $prefix.'-payroll-calc.html'],
                ['label' => 'Payslip Generator', 'icon' => 'bi-receipt', 'href' => $p($prefix.'-payslips.html'), 'permission' => 'payslips', 'page' => $prefix.'-payslips.html'],
                ['label' => 'Compliance challan', 'icon' => 'bi-calendar3', 'href' => $p($prefix.'-compliance-calendar.html'), 'permission' => 'compliance', 'page' => $prefix.'-compliance-calendar.html'],
            ]],
            ['title' => 'Attendance and Leave', 'items' => array_values(array_filter([
                ['label' => 'Attendance Sheet', 'icon' => 'bi-calendar2-check', 'href' => $p($prefix.'-attendance.html'), 'permission' => 'attendance', 'page' => $prefix.'-attendance.html'],
                $isSuper ? ['label' => 'Scan Attendance', 'icon' => 'bi-camera-video', 'href' => $p('scan-attendance.php'), 'permission' => 'attendance', 'page' => 'scan-attendance.php'] : null,
                $isSuper ? ['label' => 'Employee Face Registration', 'icon' => 'bi-person-bounding-box', 'href' => $p('face-attendance-registration.php'), 'permission' => 'attendance', 'page' => 'face-attendance-registration.php'] : null,
                $isSuper ? ['label' => 'Scan Attendance Settings', 'icon' => 'bi-sliders2', 'href' => $p('face-attendance-settings.php'), 'permission' => 'attendance', 'page' => 'face-attendance-settings.php'] : null,
                $isSuper ? ['label' => 'Face Attendance Sheet', 'icon' => 'bi-table', 'href' => $p('face-attendance-sheet.php'), 'permission' => 'attendance', 'page' => 'face-attendance-sheet.php'] : null,
                $isSuper ? ['label' => 'Monthly Attendance Report', 'icon' => 'bi-file-earmark-bar-graph', 'href' => $p('monthly-attendance-report.php'), 'permission' => 'attendance', 'page' => 'monthly-attendance-report.php'] : null,
                ['label' => 'Attendance Status', 'icon' => 'bi-ui-checks-grid', 'href' => $p($prefix.'-attendance-statuses.html'), 'permission' => 'attendanceStatus', 'page' => $prefix.'-attendance-statuses.html'],
                ['label' => 'Leave Management', 'icon' => 'bi-calendar2-check', 'href' => $p($prefix.'-leave.html'), 'permission' => 'leaveManagement', 'page' => $prefix.'-leave.html'],
                ['label' => 'Overtime', 'icon' => 'bi-clock-history', 'href' => $p($prefix.'-overtime.html'), 'permission' => 'overtime', 'page' => $prefix.'-overtime.html'],
            ]))],
            ['title' => 'Returns & Sheets', 'items' => [
                ['label' => 'FNF', 'icon' => 'bi-clipboard-check', 'href' => $p($prefix.'-fnf.html'), 'permission' => 'fnf', 'page' => $prefix.'-fnf.html'],
                ['label' => 'Advance Salary', 'icon' => 'bi-cash-coin', 'href' => $p($prefix.'-advance-salary.html'), 'permission' => 'advanceSalary', 'page' => $prefix.'-advance-salary.html'],
                ['label' => 'Loan', 'icon' => 'bi-bank', 'href' => $p($prefix.'-loan.html'), 'permission' => 'loan', 'page' => $prefix.'-loan.html'],
                ['label' => 'Gratuity', 'icon' => 'bi-award', 'href' => $p($prefix.'-gratuity.html'), 'permission' => 'gratuity', 'page' => $prefix.'-gratuity.html'],
                ['label' => 'Bonus', 'icon' => 'bi-cash-stack', 'href' => $p($prefix.'-bonus.html'), 'permission' => 'bonus', 'page' => $prefix.'-bonus.html'],
                ['label' => 'Incentive', 'icon' => 'bi-gift', 'href' => $p($prefix.'-incentive.html'), 'permission' => 'incentive', 'page' => $prefix.'-incentive.html'],
                ['label' => 'PF / ECR Sheet', 'icon' => 'bi-file-earmark-spreadsheet', 'href' => $p($prefix.'-pf-sheet.html'), 'permission' => 'pfSheet', 'page' => $prefix.'-pf-sheet.html'],
                ['label' => 'PF Return', 'icon' => 'bi-cloud-upload', 'href' => $p($prefix.'-pf-return.html'), 'permission' => 'pfReturn', 'page' => $prefix.'-pf-return.html'],
                ['label' => 'ESIC Sheet', 'icon' => 'bi-file-earmark-spreadsheet', 'href' => $p($prefix.'-esic-sheet.html'), 'permission' => 'esicSheet', 'page' => $prefix.'-esic-sheet.html'],
                ['label' => 'ESIC Return', 'icon' => 'bi-cloud-upload', 'href' => $p($prefix.'-esic-return.html'), 'permission' => 'esicReturn', 'page' => $prefix.'-esic-return.html'],
            ]],
            ['title' => 'Settings', 'items' => array_values(array_filter([
                ['label' => 'Control Page', 'icon' => 'bi-sliders', 'href' => $p($prefix.'-control.html'), 'permission' => 'controlPage', 'page' => $prefix.'-control.html'],
                $isSuper ? ['label' => 'SMTP Control', 'icon' => 'bi-envelope-gear', 'href' => $p('super-admin-smtp-control.html'), 'permission' => 'accessControl', 'page' => 'super-admin-smtp-control.html'] : null,
                $isSuper
                    ? ['label' => 'Access Control', 'icon' => 'bi-shield-lock', 'href' => $p('super-admin-access-control.html'), 'permission' => 'accessControl', 'page' => 'super-admin-access-control.html']
                    : ['label' => 'Roles', 'icon' => 'bi-shield-lock', 'href' => $p('client-roles.html'), 'permission' => 'accessControl', 'page' => 'client-roles.html'],
                $isSuper ? ['label' => 'Roles', 'icon' => 'bi-person-badge', 'href' => $p('super-admin-roles.html'), 'permission' => 'accessControl', 'page' => 'super-admin-roles.html'] : null,
            ]))],
            ['title' => 'Account', 'items' => array_values(array_filter([
                ['label' => 'Company Profile', 'icon' => 'bi-building', 'href' => $p($prefix.'-profile.html'), 'permission' => 'companyProfile', 'page' => $prefix.'-profile.html'],
                $isSuper ? ['label' => 'Enquiries', 'icon' => 'bi-chat-left-text', 'href' => $p('super-admin-enquiries.html'), 'permission' => 'accessControl', 'page' => 'super-admin-enquiries.html'] : null,
                ['label' => 'Subscriptions', 'icon' => 'bi-card-checklist', 'href' => $p($prefix.'-subscriptions.html'), 'permission' => 'subscriptions', 'page' => $prefix.'-subscriptions.html'],
                ['label' => 'Billing & Invoice', 'icon' => 'bi-receipt-cutoff', 'href' => $p($prefix.'-billing.html'), 'permission' => 'billing', 'page' => $prefix.'-billing.html'],
                ['label' => 'Logout', 'icon' => 'bi-box-arrow-right', 'href' => $p($prefix.'-logout.html'), 'permission' => null, 'page' => $prefix.'-logout.html'],
            ]))],
        ];
    }

    public static function cleanRoutes(): array
    {
        $client = [
            '/client' => 'client/index.html',
            '/client/login' => 'client/client-login.html',
            '/client/logout' => 'client/client-logout.html',
            '/client/dashboard' => 'client/index.html',
            '/client/module' => 'client/client-module.html',
            '/client/employees' => 'client/client-employee-master.html',
            '/client/employee-types' => 'client/client-employee-types.html',
            '/client/payroll' => 'client/client-payroll-calc.html',
            '/client/payslips' => 'client/client-payslips.html',
            '/client/compliance' => 'client/client-compliance-calendar.html',
            '/client/attendance' => 'client/client-attendance.html',
            '/client/attendance-statuses' => 'client/client-attendance-statuses.html',
            '/client/shift-roster' => 'client/client-shift-roster.html',
            '/client/leaves' => 'client/client-leave.html',
            '/client/overtime' => 'client/client-overtime.html',
            '/client/fnf' => 'client/client-fnf.html',
            '/client/advance-salary' => 'client/client-advance-salary.html',
            '/client/loans' => 'client/client-loan.html',
            '/client/gratuity' => 'client/client-gratuity.html',
            '/client/bonus' => 'client/client-bonus.html',
            '/client/incentive' => 'client/client-incentive.html',
            '/client/pf-sheet' => 'client/client-pf-sheet.html',
            '/client/pf-return' => 'client/client-pf-return.html',
            '/client/esic-sheet' => 'client/client-esic-sheet.html',
            '/client/esic-return' => 'client/client-esic-return.html',
            '/client/ecr-sheet' => 'client/client-ecr-sheet.html',
            '/client/control' => 'client/client-control.html',
            '/client/roles' => 'client/client-roles.html',
            '/client/profile' => 'client/client-profile.html',
            '/client/subscriptions' => 'client/client-subscriptions.html',
            '/client/billing' => 'client/client-billing.html',
        ];

        $super = [
            '/super-admin' => 'super-admin/super-admin-dashboard.html',
            '/super-admin/login' => 'super-admin/super-admin-login.html',
            '/super-admin/logout' => 'super-admin/super-admin-logout.html',
            '/super-admin/dashboard' => 'super-admin/super-admin-dashboard.html',
            '/super-admin/tenant' => 'super-admin/index.html',
            '/super-admin/module' => 'super-admin/super-admin-module.html',
            '/super-admin/employees' => 'super-admin/super-admin-employee-master.html',
            '/super-admin/employee-types' => 'super-admin/super-admin-employee-types.html',
            '/super-admin/payroll' => 'super-admin/super-admin-payroll-calc.html',
            '/super-admin/payslips' => 'super-admin/super-admin-payslips.html',
            '/super-admin/compliance' => 'super-admin/super-admin-compliance-calendar.html',
            '/super-admin/attendance' => 'super-admin/super-admin-attendance.html',
            '/super-admin/attendance-statuses' => 'super-admin/super-admin-attendance-statuses.html',
            '/super-admin/shift-roster' => 'super-admin/super-admin-shift-roster.html',
            '/super-admin/leaves' => 'super-admin/super-admin-leave.html',
            '/super-admin/overtime' => 'super-admin/super-admin-overtime.html',
            '/super-admin/fnf' => 'super-admin/super-admin-fnf.html',
            '/super-admin/advance-salary' => 'super-admin/super-admin-advance-salary.html',
            '/super-admin/loans' => 'super-admin/super-admin-loan.html',
            '/super-admin/gratuity' => 'super-admin/super-admin-gratuity.html',
            '/super-admin/bonus' => 'super-admin/super-admin-bonus.html',
            '/super-admin/incentive' => 'super-admin/super-admin-incentive.html',
            '/super-admin/pf-sheet' => 'super-admin/super-admin-pf-sheet.html',
            '/super-admin/pf-return' => 'super-admin/super-admin-pf-return.html',
            '/super-admin/esic-sheet' => 'super-admin/super-admin-esic-sheet.html',
            '/super-admin/esic-return' => 'super-admin/super-admin-esic-return.html',
            '/super-admin/ecr-sheet' => 'super-admin/super-admin-ecr-sheet.html',
            '/super-admin/control' => 'super-admin/super-admin-control.html',
            '/super-admin/access-control' => 'super-admin/super-admin-access-control.html',
            '/super-admin/roles' => 'super-admin/super-admin-roles.html',
            '/super-admin/smtp' => 'super-admin/super-admin-smtp-control.html',
            '/super-admin/profile' => 'super-admin/super-admin-profile.html',
            '/super-admin/enquiries' => 'super-admin/super-admin-enquiries.html',
            '/super-admin/subscriptions' => 'super-admin/super-admin-subscriptions.html',
            '/super-admin/billing' => 'super-admin/super-admin-billing.html',
            '/super-admin/face/scan' => 'super-admin/scan-attendance.php',
            '/super-admin/face/register' => 'super-admin/face-attendance-registration.php',
            '/super-admin/face/settings' => 'super-admin/face-attendance-settings.php',
            '/super-admin/face/sheet' => 'super-admin/face-attendance-sheet.php',
            '/super-admin/face/report' => 'super-admin/monthly-attendance-report.php',
        ];

        return array_merge($client, $super);
    }
}
