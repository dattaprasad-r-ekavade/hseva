<?php

namespace App\Support;

class PageRegistry
{
    /** @var array<string, array<string, mixed>> */
    private const PAGES = array (
  'client/client-module.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.module',
    'title' => 'Client Module',
    'pageTitle' => 'Client Module',
    'pageSubtitle' => 'Create and manage multiple client companies',
    'styles' => 
    array (
      0 => 'client-module.css',
    ),
    'scripts' => 
    array (
      0 => 'client-module.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/super-admin-module.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.module',
    'title' => 'Client Module',
    'pageTitle' => 'Client Module',
    'pageSubtitle' => '-',
    'styles' => 
    array (
      0 => 'client-module.css',
    ),
    'scripts' => 
    array (
      0 => 'client-module.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-employee-master.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.employee-master',
    'title' => 'Employee Master',
    'pageTitle' => 'Employee Master',
    'pageSubtitle' => 'Add employees and manage salary inputs from control settings',
    'styles' => 
    array (
      0 => 'client-employee-master.css',
    ),
    'scripts' => 
    array (
      0 => 'client-employee-master.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
  ),
  'super-admin/super-admin-employee-master.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.employee-master',
    'title' => 'Employee Master',
    'pageTitle' => 'Employee Master',
    'pageSubtitle' => 'Control Month',
    'styles' => 
    array (
      0 => 'client-employee-master.css',
    ),
    'scripts' => 
    array (
      0 => 'client-employee-master.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/employee-profile.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.employee-profile',
    'title' => 'Employee Profile | Client Portal',
    'pageTitle' => 'Employee Profile',
    'pageSubtitle' => 'View employee details',
    'styles' => 
    array (
      0 => 'employee-profile.css',
    ),
    'scripts' => 
    array (
      0 => 'employee-profile.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/employee-profile.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.employee-profile',
    'title' => 'Employee Profile | Client Portal',
    'pageTitle' => 'Employee Profile',
    'pageSubtitle' => 'View employee details',
    'styles' => 
    array (
      0 => 'employee-profile.css',
    ),
    'scripts' => 
    array (
      0 => 'employee-profile.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-payroll-calc.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.payroll-calc',
    'title' => 'Salary Sheet | Client Portal',
    'pageTitle' => 'Salary Sheet',
    'pageSubtitle' => 'Total Generated',
    'styles' => 
    array (
      0 => 'client-payroll-calc.css',
    ),
    'scripts' => 
    array (
      0 => 'client-payroll-calc.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
      1 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
  ),
  'super-admin/super-admin-payroll-calc.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.payroll-calc',
    'title' => 'Salary Sheet | Client Portal',
    'pageTitle' => 'Salary Sheet',
    'pageSubtitle' => 'Total Generated',
    'styles' => 
    array (
      0 => 'client-payroll-calc.css',
    ),
    'scripts' => 
    array (
      0 => 'client-payroll-calc.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
      1 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-payslips.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.payslips',
    'title' => 'Client Portal | Payslips',
    'pageTitle' => 'Client Portal | Payslips',
    'pageSubtitle' => '',
    'styles' => 
    array (
      0 => 'client-payslips.css',
    ),
    'scripts' => 
    array (
      0 => 'client-payslips.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
      1 => 'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js',
    ),
  ),
  'super-admin/super-admin-payslips.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.payslips',
    'title' => 'Client Portal | Payslips',
    'pageTitle' => 'Client Portal | Payslips',
    'pageSubtitle' => '',
    'styles' => 
    array (
      0 => 'client-payslips.css',
    ),
    'scripts' => 
    array (
      0 => 'client-payslips.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
      1 => 'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-compliance-calendar.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.compliance-calendar',
    'title' => 'Compliance challan | Client Portal',
    'pageTitle' => 'Compliance challan',
    'pageSubtitle' => 'Month-wise tasks with status tracking',
    'styles' => 
    array (
      0 => 'client-compliance-calendar.css',
    ),
    'scripts' => 
    array (
      0 => 'client-compliance-calendar.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/super-admin-compliance-calendar.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.compliance-calendar',
    'title' => 'Compliance challan | Client Portal',
    'pageTitle' => 'Compliance challan',
    'pageSubtitle' => 'Month-wise tasks with status tracking',
    'styles' => 
    array (
      0 => 'client-compliance-calendar.css',
    ),
    'scripts' => 
    array (
      0 => 'client-compliance-calendar.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-attendance.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.attendance',
    'title' => 'Attendance | Client Portal',
    'pageTitle' => 'Attendance Sheet',
    'pageSubtitle' => 'Daily records + monthly sheet generation + import/export',
    'styles' => 
    array (
      0 => 'client-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'client-attendance.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
      1 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
  ),
  'super-admin/super-admin-attendance.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.attendance',
    'title' => 'Attendance | Client Portal',
    'pageTitle' => 'Attendance Sheet',
    'pageSubtitle' => 'Daily records + monthly sheet generation + import/export',
    'styles' => 
    array (
      0 => 'client-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'client-attendance.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
      1 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-shift-roster.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.shift-roster',
    'title' => 'Shift & Roster Management | Client',
    'pageTitle' => 'Shift / Roster Management',
    'pageSubtitle' => 'Company-level shift and roster operations',
    'styles' => 
    array (
      0 => 'shift-roster.css',
    ),
    'scripts' => 
    array (
      0 => 'shift-roster.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'super-admin/super-admin-shift-roster.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.shift-roster',
    'title' => 'Shift & Roster Management | Super Admin',
    'pageTitle' => 'Shift / Roster Management',
    'pageSubtitle' => 'Centralized super admin operations across companies',
    'styles' => 
    array (
      0 => 'shift-roster.css',
    ),
    'scripts' => 
    array (
      0 => 'shift-roster.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-leave.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.leave',
    'title' => 'Attendance & Leave | Client Portal',
    'pageTitle' => 'Leave Management',
    'pageSubtitle' => 'Apply leave for employees (search by name / Emp ID)',
    'styles' => 
    array (
      0 => 'client-leave.css',
    ),
    'scripts' => 
    array (
      0 => 'client-leave.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
      1 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
  ),
  'super-admin/super-admin-leave.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.leave',
    'title' => 'Attendance & Leave | Client Portal',
    'pageTitle' => 'Leave Management',
    'pageSubtitle' => 'Apply leave for employees (search by name / Emp ID)',
    'styles' => 
    array (
      0 => 'client-leave.css',
    ),
    'scripts' => 
    array (
      0 => 'client-leave.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
      1 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-overtime.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.overtime',
    'title' => 'Overtime | Client Portal',
    'pageTitle' => 'Overtime Module',
    'pageSubtitle' => 'Record overtime hours and calculate OT amount automatically.',
    'styles' => 
    array (
      0 => 'overtime.css',
    ),
    'scripts' => 
    array (
      0 => 'overtime.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/super-admin-overtime.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.overtime',
    'title' => 'Overtime | Super Admin',
    'pageTitle' => 'Overtime Module',
    'pageSubtitle' => 'Record overtime hours and calculate OT amount automatically.',
    'styles' => 
    array (
      0 => 'overtime.css',
    ),
    'scripts' => 
    array (
      0 => 'overtime.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'client/client-fnf.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.fnf',
    'title' => 'FNF Settlement | Client Portal',
    'pageTitle' => 'Full and Final',
    'pageSubtitle' => 'Total Generated',
    'styles' => 
    array (
      0 => 'client-fnf.css',
    ),
    'scripts' => 
    array (
      0 => 'client-fnf.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
      1 => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
      2 => 'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js',
    ),
  ),
  'super-admin/super-admin-fnf.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.fnf',
    'title' => 'FNF Settlement | Client Portal',
    'pageTitle' => 'Full and Final',
    'pageSubtitle' => 'Total Generated',
    'styles' => 
    array (
      0 => 'client-fnf.css',
    ),
    'scripts' => 
    array (
      0 => 'client-fnf.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
      1 => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
      2 => 'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-advance-salary.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.advance-salary',
    'title' => 'Advance Salary Module | Client Portal',
    'pageTitle' => 'Advance Salary Module',
    'pageSubtitle' => 'Generate attendance-based advances and let payroll deduct them automatically.',
    'styles' => 
    array (
      0 => 'advance-salary.css',
    ),
    'scripts' => 
    array (
      0 => 'advance-salary.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/super-admin-advance-salary.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.advance-salary',
    'title' => 'Advance Salary Module | Super Admin',
    'pageTitle' => 'Advance Salary Module',
    'pageSubtitle' => 'Generate attendance-based advances and let payroll deduct them automatically.',
    'styles' => 
    array (
      0 => 'advance-salary.css',
    ),
    'scripts' => 
    array (
      0 => 'advance-salary.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'client/client-loan.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.loan',
    'title' => 'Loan Module | Client Portal',
    'pageTitle' => 'Loan Module',
    'pageSubtitle' => 'Create employee loan requests with repayment setup and automatic payroll recovery.',
    'styles' => 
    array (
      0 => 'client-fnf.css',
    ),
    'scripts' => 
    array (
      0 => 'client-loan.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/super-admin-loan.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.loan',
    'title' => 'Loan Module | Super Admin',
    'pageTitle' => 'Loan Module',
    'pageSubtitle' => 'Create employee loan requests with repayment setup and automatic payroll recovery.',
    'styles' => 
    array (
      0 => 'client-fnf.css',
    ),
    'scripts' => 
    array (
      0 => 'client-loan.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'client/client-view-loan.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.view-loan',
    'title' => 'View Loan Details | Client Portal',
    'pageTitle' => 'Loan Details',
    'pageSubtitle' => 'Employee, loan, recovery summary, and EMI deduction history.',
    'styles' => 
    array (
      0 => 'client-fnf.css',
    ),
    'scripts' => 
    array (
      0 => 'view-loan.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/super-admin-view-loan.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.view-loan',
    'title' => 'View Loan Details | Super Admin',
    'pageTitle' => 'Loan Details',
    'pageSubtitle' => 'Employee, loan, recovery summary, and EMI deduction history.',
    'styles' => 
    array (
      0 => 'client-fnf.css',
    ),
    'scripts' => 
    array (
      0 => 'view-loan.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'client/client-gratuity.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.gratuity',
    'title' => 'Gratuity | Client Portal',
    'pageTitle' => 'Gratuity',
    'pageSubtitle' => 'Generated gratuity preview',
    'styles' => 
    array (
      0 => 'client-fnf.css',
    ),
    'scripts' => 
    array (
      0 => 'client-gratuity.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
    ),
  ),
  'super-admin/super-admin-gratuity.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.gratuity',
    'title' => 'Gratuity | Super Admin',
    'pageTitle' => 'Gratuity',
    'pageSubtitle' => 'Generated gratuity preview',
    'styles' => 
    array (
      0 => 'client-fnf.css',
    ),
    'scripts' => 
    array (
      0 => 'client-gratuity.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-bonus.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.bonus',
    'title' => 'Bonus | Client Portal',
    'pageTitle' => 'Bonus',
    'pageSubtitle' => 'Generated bonus preview',
    'styles' => 
    array (
      0 => 'client-fnf.css',
    ),
    'scripts' => 
    array (
      0 => 'client-bonus.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/super-admin-bonus.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.bonus',
    'title' => 'Bonus | Super Admin',
    'pageTitle' => 'Bonus',
    'pageSubtitle' => 'Generated bonus preview',
    'styles' => 
    array (
      0 => 'client-fnf.css',
    ),
    'scripts' => 
    array (
      0 => 'client-bonus.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'client/client-incentive.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.incentive',
    'title' => 'Incentive | Client Portal',
    'pageTitle' => 'Incentive',
    'pageSubtitle' => '0 records | Rs 0.00',
    'styles' => 
    array (
      0 => 'client-fnf.css',
    ),
    'scripts' => 
    array (
      0 => 'client-incentive.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/super-admin-incentive.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.incentive',
    'title' => 'Incentive | Super Admin',
    'pageTitle' => 'Incentive',
    'pageSubtitle' => '0 records | Rs 0.00',
    'styles' => 
    array (
      0 => 'client-fnf.css',
    ),
    'scripts' => 
    array (
      0 => 'client-incentive.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'client/client-pf-sheet.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.pf-sheet',
    'title' => 'PF Sheet | Client Portal',
    'pageTitle' => 'PF Sheet',
    'pageSubtitle' => 'Select Month/Year -> Generate -> Saved list with View & Download',
    'styles' => 
    array (
      0 => 'client-pf-sheet.css',
    ),
    'scripts' => 
    array (
      0 => 'client-pf-sheet.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
  ),
  'super-admin/super-admin-pf-sheet.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.pf-sheet',
    'title' => 'PF Sheet | Client Portal',
    'pageTitle' => 'PF Sheet',
    'pageSubtitle' => 'Select Month/Year -> Generate -> Saved list with View & Download',
    'styles' => 
    array (
      0 => 'client-pf-sheet.css',
    ),
    'scripts' => 
    array (
      0 => 'client-pf-sheet.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-pf-return.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.pf-return',
    'title' => 'PF Return | Client Portal',
    'pageTitle' => 'PF Return',
    'pageSubtitle' => 'Upload monthly challan PDF and save metadata.',
    'styles' => 
    array (
      0 => 'client-pf-return.css',
    ),
    'scripts' => 
    array (
      0 => 'client-pf-return.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
  ),
  'super-admin/super-admin-pf-return.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.pf-return',
    'title' => 'PF Return | Client Portal',
    'pageTitle' => 'PF Return',
    'pageSubtitle' => 'Upload monthly challan PDF and save metadata.',
    'styles' => 
    array (
      0 => 'client-pf-return.css',
    ),
    'scripts' => 
    array (
      0 => 'client-pf-return.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-esic-sheet.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.esic-sheet',
    'title' => 'ESIC Sheet | Client Portal',
    'pageTitle' => 'ESIC Sheet',
    'pageSubtitle' => 'Generate -> Save list -> View/Download',
    'styles' => 
    array (
      0 => 'client-profile.css',
    ),
    'scripts' => 
    array (
      0 => 'client-esic-sheet.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
  ),
  'super-admin/super-admin-esic-sheet.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.esic-sheet',
    'title' => 'ESIC Sheet | Client Portal',
    'pageTitle' => 'ESIC Sheet',
    'pageSubtitle' => 'Generate -> Save list -> View/Download',
    'styles' => 
    array (
      0 => 'client-profile.css',
    ),
    'scripts' => 
    array (
      0 => 'client-esic-sheet.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-esic-return.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.esic-return',
    'title' => 'ESIC Return | Client Portal',
    'pageTitle' => 'ESIC Return',
    'pageSubtitle' => 'Upload challan PDF and keep ESIC return history',
    'styles' => 
    array (
      0 => 'client-esic-return.css',
    ),
    'scripts' => 
    array (
      0 => 'client-esic-return.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
  ),
  'super-admin/super-admin-esic-return.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.esic-return',
    'title' => 'ESIC Return | Client Portal',
    'pageTitle' => 'ESIC Return',
    'pageSubtitle' => 'Upload monthly challan PDF and save metadata.',
    'styles' => 
    array (
      0 => 'client-esic-return.css',
    ),
    'scripts' => 
    array (
      0 => 'client-esic-return.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-ecr-sheet.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.ecr-sheet',
    'title' => 'ECR Sheet | Client Portal',
    'pageTitle' => 'ECR Sheet',
    'pageSubtitle' => 'Generate -> Save list -> Download XLSX',
    'styles' => 
    array (
      0 => 'client-profile.css',
    ),
    'scripts' => 
    array (
      0 => 'client-ecr-sheet.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
  ),
  'super-admin/super-admin-ecr-sheet.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.ecr-sheet',
    'title' => 'ECR Sheet | Client Portal',
    'pageTitle' => 'ECR Sheet',
    'pageSubtitle' => 'Generate -> Save list -> Download XLSX',
    'styles' => 
    array (
      0 => 'client-profile.css',
    ),
    'scripts' => 
    array (
      0 => 'client-ecr-sheet.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-control.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.control',
    'title' => 'Control Page | Client Portal',
    'pageTitle' => 'Control Page',
    'pageSubtitle' => 'Statutory rates, CTC breakup, and company master details',
    'styles' => 
    array (
      0 => 'client-control.css',
    ),
    'scripts' => 
    array (
      0 => 'client-control.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/super-admin-control.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.control',
    'title' => 'Control Page | Client Portal',
    'pageTitle' => 'Control Page',
    'pageSubtitle' => 'Statutory rates, CTC breakup, and company master details',
    'styles' => 
    array (
      0 => 'client-control.css',
    ),
    'scripts' => 
    array (
      0 => 'client-control.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-roles.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.roles',
    'title' => 'Roles',
    'pageTitle' => 'Roles',
    'pageSubtitle' => 'Create internal staff roles and assign employee access',
    'styles' => 
    array (
      0 => 'client-access-control.css',
    ),
    'scripts' => 
    array (
      0 => 'client-roles.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/super-admin-roles.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.roles',
    'title' => 'Super Admin | Roles',
    'pageTitle' => 'Roles',
    'pageSubtitle' => 'Create internal staff roles and assign employee access',
    'styles' => 
    array (
      0 => 'client-access-control.css',
    ),
    'scripts' => 
    array (
      0 => 'client-roles.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-access-control.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.access-control',
    'title' => 'Access Control',
    'pageTitle' => 'Access Control',
    'pageSubtitle' => 'Grant or revoke module access for each client user',
    'styles' => 
    array (
      0 => 'client-access-control.css',
    ),
    'scripts' => 
    array (
      0 => 'client-access-control.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/super-admin-access-control.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.access-control',
    'title' => 'Access Control',
    'pageTitle' => 'Access Control',
    'pageSubtitle' => '0 records',
    'styles' => 
    array (
      0 => 'client-access-control.css',
    ),
    'scripts' => 
    array (
      0 => 'client-access-control.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-profile.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.profile',
    'title' => 'Company Profile | Client Portal',
    'pageTitle' => 'Company Profile',
    'pageSubtitle' => 'Company details + statutory IDs + contacts',
    'styles' => 
    array (
      0 => 'client-profile.css',
    ),
    'scripts' => 
    array (
      0 => 'client-profile.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/super-admin-profile.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.profile',
    'title' => 'Company Profile | Client Portal',
    'pageTitle' => 'Company Profile',
    'pageSubtitle' => 'Company details + statutory IDs + contacts',
    'styles' => 
    array (
      0 => 'client-profile.css',
    ),
    'scripts' => 
    array (
      0 => 'client-profile.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/index.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.dashboard',
    'topbarView' => 'pages.topbars.dashboard',
    'showNotifications' => true,
    'title' => 'Client Dashboard | HR Compliance Portal',
    'pageTitle' => 'Dashboard',
    'pageSubtitle' => 'Welcome back, -',
    'styles' => 
    array (
      0 => 'index.css',
    ),
    'scripts' => 
    array (
      0 => 'index.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'super-admin/index.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.dashboard',
    'topbarView' => 'pages.topbars.dashboard',
    'showNotifications' => true,
    'title' => 'Client Dashboard | HR Compliance Portal',
    'pageTitle' => 'Client Dashboard',
    'pageSubtitle' => 'Welcome back, -',
    'styles' => 
    array (
      0 => 'index.css',
    ),
    'scripts' => 
    array (
      0 => 'index.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/scan-attendance.php' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.face-scan',
    'title' => 'Scan Attendance | HR Seva',
    'pageTitle' => 'Scan Attendance',
    'pageSubtitle' => 'IN Scan works by default. Click OUT Scan before scanning if you want to mark employee exit.',
    'styles' => 
    array (
      0 => 'face-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'face-attendance.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js',
    ),
    'bodyAttrs' => 
    array (
      'data-face-page' => 'scan',
    ),
  ),
  'super-admin/scan-attendance.php' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.face-scan',
    'title' => 'Scan Attendance | Super Admin | HR Seva',
    'pageTitle' => 'Scan Attendance',
    'pageSubtitle' => 'IN Scan works by default. Click OUT Scan before scanning if you want to mark employee exit.',
    'styles' => 
    array (
      0 => 'face-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'face-attendance.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js',
    ),
    'bodyAttrs' => 
    array (
      'data-face-page' => 'scan',
    ),
  ),
  'client/face-attendance-registration.php' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.face-registration',
    'title' => 'Employee Face Registration | HR Seva',
    'pageTitle' => 'Employee Face Registration',
    'pageSubtitle' => 'Register one face template per employee using the webcam.',
    'styles' => 
    array (
      0 => 'face-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'face-attendance.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js',
    ),
    'bodyAttrs' => 
    array (
      'data-face-page' => 'register',
    ),
  ),
  'super-admin/face-attendance-registration.php' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.face-registration',
    'title' => 'Employee Face Registration | Super Admin | HR Seva',
    'pageTitle' => 'Employee Face Registration',
    'pageSubtitle' => 'Register one face template per employee for the selected client.',
    'styles' => 
    array (
      0 => 'face-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'face-attendance.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js',
    ),
    'bodyAttrs' => 
    array (
      'data-face-page' => 'register',
    ),
  ),
  'client/face-attendance-settings.php' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.face-settings',
    'title' => 'Scan Attendance Settings | HR Seva',
    'pageTitle' => 'Scan Attendance Settings',
    'pageSubtitle' => 'Define IN/OUT rules, matching threshold, and model location.',
    'styles' => 
    array (
      0 => 'face-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'face-attendance.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyAttrs' => 
    array (
      'data-face-page' => 'settings',
    ),
  ),
  'super-admin/face-attendance-settings.php' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.face-settings',
    'title' => 'Scan Attendance Settings | Super Admin | HR Seva',
    'pageTitle' => 'Scan Attendance Settings',
    'pageSubtitle' => 'Set face attendance rules for the selected client.',
    'styles' => 
    array (
      0 => 'face-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'face-attendance.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyAttrs' => 
    array (
      'data-face-page' => 'settings',
    ),
  ),
  'client/face-attendance-sheet.php' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.face-sheet',
    'title' => 'Face Attendance Sheet | HR Seva',
    'pageTitle' => 'Face Attendance Sheet',
    'pageSubtitle' => 'View date-wise face attendance with IN/OUT details.',
    'styles' => 
    array (
      0 => 'face-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'face-attendance.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyAttrs' => 
    array (
      'data-face-page' => 'sheet',
    ),
  ),
  'super-admin/face-attendance-sheet.php' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.face-sheet',
    'title' => 'Face Attendance Sheet | Super Admin | HR Seva',
    'pageTitle' => 'Face Attendance Sheet',
    'pageSubtitle' => 'View client-wise date-wise face attendance records.',
    'styles' => 
    array (
      0 => 'face-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'face-attendance.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyAttrs' => 
    array (
      'data-face-page' => 'sheet',
    ),
  ),
  'client/monthly-attendance-report.php' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.face-monthly-report',
    'title' => 'Monthly Attendance Report | HR Seva',
    'pageTitle' => 'Monthly Attendance Report',
    'pageSubtitle' => 'Summarize present, late, early-out, and missing-out counts by employee.',
    'styles' => 
    array (
      0 => 'face-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'face-attendance.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
    'bodyAttrs' => 
    array (
      'data-face-page' => 'report',
    ),
  ),
  'super-admin/monthly-attendance-report.php' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.face-monthly-report',
    'title' => 'Monthly Attendance Report | Super Admin | HR Seva',
    'pageTitle' => 'Monthly Attendance Report',
    'pageSubtitle' => 'Export monthly face attendance reports for the selected client.',
    'styles' => 
    array (
      0 => 'face-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'face-attendance.js',
    ),
    'cdnScripts' => 
    array (
      0 => 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js',
    ),
    'bodyAttrs' => 
    array (
      'data-face-page' => 'report',
    ),
  ),
  'client/client-login.html' => 
  array (
    'layout' => 'auth',
    'contentView' => 'auth.client-login',
    'title' => 'Client Login | HR Seva',
    'style' => 'client-login.css',
    'script' => 'client-login.js?v=20260507',
  ),
  'client/client-logout.html' => 
  array (
    'layout' => 'minimal',
    'contentView' => 'auth.client-logout',
    'title' => 'Logging out...',
    'script' => 'client-logout.js',
    'loadAppCommon' => false,
  ),
  'client/client-invoices.html' => 
  array (
    'redirect' => '/client/client-billing.html',
  ),
  'client/client-subscriptions.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.client-subscriptions',
    'title' => 'Client Subscriptions',
    'pageTitle' => 'Subscriptions',
    'pageSubtitle' => 'Your current subscription and available plans',
    'styles' => 
    array (
      0 => 'client-subscriptions.css',
    ),
    'scripts' => 
    array (
      0 => 'client-subscriptions.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'client/client-billing.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.client-billing',
    'title' => 'Client Billing & Invoice',
    'pageTitle' => 'Billing & Invoice',
    'pageSubtitle' => 'Your billing summary and monthly charges',
    'styles' => 
    array (
      0 => 'client-billing-invoices.css',
    ),
    'scripts' => 
    array (
      0 => 'client-billing.js',
    ),
    'cdnScripts' => 
    array (
    ),
  ),
  'client/client-employee-types.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.client-employee-types',
    'title' => 'Employee Type | Client Portal',
    'pageTitle' => 'Employee Type',
    'pageSubtitle' => 'View the employee types currently available in Employee Master.',
    'styles' => 
    array (
    ),
    'scripts' => 
    array (
      0 => 'client-employee-types.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/client-attendance-statuses.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.client-attendance-statuses',
    'title' => 'Attendance Status | Client Portal',
    'pageTitle' => 'Attendance Status',
    'pageSubtitle' => 'View the active attendance statuses configured by Super Admin for attendance marking and payroll behavior.',
    'styles' => 
    array (
    ),
    'scripts' => 
    array (
      0 => 'client-attendance-statuses.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/my-shift-roster.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.my-shift-roster',
    'title' => 'My Shift / Roster',
    'pageTitle' => 'My Shift / Roster',
    'pageSubtitle' => '',
    'styles' => 
    array (
      0 => 'shift-roster.css',
    ),
    'scripts' => 
    array (
      0 => 'my-shift-roster.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'client/my-face-attendance.php' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.my-face-attendance',
    'title' => 'My Attendance | HR Seva',
    'pageTitle' => 'My Attendance',
    'pageSubtitle' => 'See your own face attendance records for the current month.',
    'styles' => 
    array (
      0 => 'face-attendance.css',
    ),
    'scripts' => 
    array (
      0 => 'face-attendance.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyAttrs' => 
    array (
      'data-face-page' => 'my-attendance',
    ),
  ),
  'super-admin/super-admin-login.html' => 
  array (
    'layout' => 'auth',
    'contentView' => 'auth.super-admin-login',
    'title' => 'Super Admin Login | HR Seva',
    'style' => 'super-admin-login.css',
    'script' => 'super-admin-login.js',
  ),
  'super-admin/super-admin-logout.html' => 
  array (
    'layout' => 'minimal',
    'contentView' => 'auth.super-admin-logout',
    'title' => 'Logging out...',
    'script' => 'client-logout.js',
    'loadAppCommon' => false,
  ),
  'super-admin/super-admin-invoices.html' => 
  array (
    'redirect' => '/super-admin/super-admin-billing.html',
  ),
  'super-admin/super-admin-dashboard.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.super-admin-dashboard',
    'title' => 'Super Admin Dashboard | HR Compliance Portal',
    'pageTitle' => 'Super Admin Dashboard',
    'pageSubtitle' => 'Control clients, access types, and platform usage',
    'styles' => 
    array (
      0 => 'super-admin-dashboard.css',
    ),
    'scripts' => 
    array (
      0 => 'super-admin-dashboard.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'super-admin/super-admin-enquiries.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.super-admin-enquiries',
    'title' => 'Enquiries | Super Admin',
    'pageTitle' => 'Landing Enquiries',
    'pageSubtitle' => 'Shows only the fields collected by the landing page free-trial popup form.',
    'styles' => 
    array (
      0 => 'super-admin-enquiries.css',
    ),
    'scripts' => 
    array (
      0 => 'super-admin-enquiries.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'super-admin/super-admin-smtp-control.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.super-admin-smtp-control',
    'title' => 'SMTP Control | Super Admin',
    'pageTitle' => 'SMTP Control',
    'pageSubtitle' => 'Manage Hostinger SMTP settings, sender identity, and test delivery from one place.',
    'styles' => 
    array (
    ),
    'scripts' => 
    array (
      0 => 'super-admin-smtp-control.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'super-admin/super-admin-subscriptions.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.super-admin-subscriptions',
    'title' => 'Subscriptions | Super Admin',
    'pageTitle' => 'Subscriptions',
    'pageSubtitle' => 'Track plans, validity, renewal and status',
    'styles' => 
    array (
      0 => 'super-admin-dashboard.css',
    ),
    'scripts' => 
    array (
      0 => 'super-admin-subscriptions.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'super-admin/super-admin-billing.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.super-admin-billing',
    'title' => 'Client Billing & Invoice',
    'pageTitle' => 'Billing & Invoice',
    'pageSubtitle' => 'Your billing summary and monthly charges',
    'styles' => 
    array (
      0 => 'client-billing-invoices.css',
    ),
    'scripts' => 
    array (
      0 => 'super-admin-billing.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'super-admin/super-admin-employee-types.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.super-admin-employee-types',
    'title' => 'Employee Type | Super Admin',
    'pageTitle' => 'Employee Type',
    'pageSubtitle' => 'Create, activate, reorder, or delete employee types used in Employee Master.',
    'styles' => 
    array (
    ),
    'scripts' => 
    array (
      0 => 'super-admin-employee-types.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  'super-admin/super-admin-attendance-statuses.html' => 
  array (
    'layout' => 'portal',
    'contentView' => 'pages.content.super-admin-attendance-statuses',
    'title' => 'Attendance Status | Super Admin',
    'pageTitle' => 'Attendance Status',
    'pageSubtitle' => 'Create, update, activate, or delete attendance statuses used in Attendance and Leave.',
    'styles' => 
    array (
    ),
    'scripts' => 
    array (
      0 => 'super-admin-attendance-statuses.js',
    ),
    'cdnScripts' => 
    array (
    ),
    'bodyClass' => 'hr-header-loading',
  ),
  '/' => 
  array (
    'layout' => 'landing',
  ),
);

    public static function get(string $key): ?array
    {
        return self::PAGES[$key] ?? null;
    }

    public static function keys(): array
    {
        return array_keys(self::PAGES);
    }
}
