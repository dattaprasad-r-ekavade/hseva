<?php

namespace App\Services\Attendance;

use App\Services\Storage\SheetStorageService;

class AttendanceGenerator
{
    public function __construct(private SheetStorageService $sheets) {}

    public function generate(int $month, int $year, bool $fillDefault = true, bool $sundayWeeklyOff = true): array
    {
        $clientId = req_client_id();
        $daily = $this->sheets->attendanceDaily($month, $year);
        $emps = employees_active_all();
        $dim = (int) cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $rows = [];

        foreach ($emps as $e) {
            $c = ['P' => 0.0, 'A' => 0.0, 'WO' => 0.0, 'CL' => 0.0, 'SL' => 0.0, 'EL' => 0.0, 'LOP' => 0.0];
            for ($d = 1; $d <= $dim; $d++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $st = strtoupper((string) ($daily[$e['id'].'|'.$date] ?? ''));
                if ($st === '' && $fillDefault) {
                    $dow = (int) date('w', strtotime($date));
                    $st = ($sundayWeeklyOff && $dow === 0) ? 'WO' : 'P';
                }
                if (! isset($c[$st])) {
                    $st = 'P';
                }
                $c[$st] += 1;
            }
            $rows[] = [
                'month' => period($month, $year),
                'empId' => $e['id'],
                'empName' => $e['name'],
                'dept' => $e['dept'],
                'desig' => $e['desig'],
                'daysInMonth' => $dim,
                'P' => $c['P'],
                'A' => $c['A'],
                'WO' => $c['WO'],
                'CL' => $c['CL'],
                'SL' => $c['SL'],
                'EL' => $c['EL'],
                'LOP' => $c['LOP'],
            ];
        }

        $sheet = $this->sheets->save('attendance_sheet', $month, $year, $rows);
        mail_sheet_event('attendance_sheet', $clientId, $sheet, 'Attendance Sheet');

        return $sheet;
    }
}
