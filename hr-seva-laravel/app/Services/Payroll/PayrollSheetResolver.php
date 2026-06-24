<?php

namespace App\Services\Payroll;

use App\Services\Attendance\AttendanceGenerator;
use App\Services\Storage\SheetStorageService;

class PayrollSheetResolver
{
    public function __construct(
        private SheetStorageService $sheets,
        private PayrollGenerator $payroll,
        private AttendanceGenerator $attendance,
    ) {}

    public function payroll(int $month, int $year): array
    {
        $item = find_period($this->sheets->index('payroll_sheet'), $month, $year);
        if ($item) {
            $sheet = $this->sheets->get('payroll_sheet', (string) $item['id']);

            return is_array($sheet) ? $sheet : nf('Payroll sheet not found');
        }

        return $this->payroll->generate($month, $year, 'LOP');
    }

    public function attendance(int $month, int $year): array
    {
        $item = find_period($this->sheets->index('attendance_sheet'), $month, $year);
        if ($item) {
            $sheet = $this->sheets->get('attendance_sheet', (string) $item['id']);

            return is_array($sheet) ? $sheet : nf('Attendance sheet not found');
        }

        return $this->attendance->generate($month, $year, true, true);
    }
}
