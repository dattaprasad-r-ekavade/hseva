<?php

namespace App\Support;

class HrSevaDefaults
{
    public const CONTROL = [
        'pfEmpPct' => 12.0, 'pfErPct' => 13.0, 'esiEmpPct' => 0.75, 'esiErPct' => 3.25, 'esiWageLimit' => 21000,
        'ptMonthly' => 200, 'ptEnabled' => 'Yes', 'pfWageCapEnabled' => 'Yes', 'pfWageCapAmount' => 15000, 'pfOnEsiPct' => 70.0, 'daPctBasic' => 0,
        'lwfEnabled' => 'Yes', 'lwfEmpAmt' => 20, 'lwfErAmt' => 40, 'lwfMonth' => 0,
        'bonusEnabled' => 'Yes', 'bonusMinimumWage' => 0.0, 'bonusMultiplierMonths' => 12.0, 'bonusPercent' => 8.33,
        'gratuityMode' => 'after_5yr', 'gratuityMinYears' => 5.0,
        'ctcBasicPct' => 50.0, 'ctcHraPct' => 10.0, 'ctcConvPct' => 0.0, 'ctcDaPct' => 30.0, 'ctcEduPct' => 0.0, 'ctcSpecialPct' => 0.0,
        'incomeTaxSlabs' => [
            ['income' => 'Up to Rs 3L', 'taxPct' => 0],
            ['income' => 'Rs 3L - Rs 6L', 'taxPct' => 5],
            ['income' => 'Rs 6L - Rs 9L', 'taxPct' => 10],
            ['income' => 'Rs 9L - Rs 12L', 'taxPct' => 15],
            ['income' => 'Rs 12L - Rs 15L', 'taxPct' => 20],
            ['income' => 'Above Rs 15L', 'taxPct' => 30],
        ],
        'ctcAddonRows' => [
            ['code' => 'pfEmployerPct', 'name' => 'PF Employer %', 'type' => 'percent', 'value' => 13.0],
            ['code' => 'esiEmployerPct', 'name' => 'ESI Employer %', 'type' => 'percent', 'value' => 3.25],
        ],
        'companyName' => '', 'companyAddress' => '',
        'companyRegNo' => '', 'companyPAN' => '', 'companyTAN' => '', 'companyGSTIN' => '', 'companyContact' => '',
    ];

    public const PROFILE = [
        'companyName' => '', 'companyAddress' => '',
        'city' => '', 'state' => '', 'pincode' => '', 'country' => '', 'website' => '',
        'regNo' => '', 'pan' => '', 'tan' => '', 'gstin' => '',
        'pfEstId' => '', 'esicCode' => '', 'contactName' => '', 'contactNo' => '', 'email' => '', 'altContactNo' => '', 'notes' => '',
    ];

    public const AUTH_USERS = [
        ['username' => 'admin', 'password' => '123456', 'name' => 'Admin', 'role' => 'super_admin'],
        ['username' => 'admin@hrseva.com', 'password' => '123456', 'name' => 'Admin', 'role' => 'super_admin'],
    ];

    public const PERMISSIONS = [
        'dashboard' => true, 'clientModule' => true, 'employeeMaster' => true, 'employeeType' => true,
        'salarySheet' => true, 'payslips' => true, 'compliance' => true, 'attendance' => true,
        'attendanceStatus' => true, 'leaveManagement' => true, 'fnf' => true, 'gratuity' => true,
        'bonus' => true, 'incentive' => true, 'loan' => true, 'pfSheet' => true, 'pfReturn' => true,
        'esicSheet' => true, 'esicReturn' => true, 'ecrSheet' => true, 'controlPage' => true,
        'companyProfile' => true, 'subscriptions' => true, 'billing' => true, 'invoices' => true,
        'accessControl' => false, 'shiftRoster' => true, 'advanceSalary' => true,
    ];
}
