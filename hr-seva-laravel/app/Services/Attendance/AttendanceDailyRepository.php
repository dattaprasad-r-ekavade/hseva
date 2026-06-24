<?php

namespace App\Services\Attendance;

use App\Services\Storage\SheetStorageService;

class AttendanceDailyRepository
{
    public function __construct(private SheetStorageService $sheets) {}

    public function list(int $month, int $year): array
    {
        $map = $this->sheets->attendanceDaily($month, $year);
        $out = [];
        foreach ($map as $k => $st) {
            $p = explode('|', (string) $k, 2);
            if (count($p) === 2) {
                $out[] = ['empId' => $p[0], 'date' => $p[1], 'status' => strtoupper((string) $st)];
            }
        }
        usort($out, fn ($a, $b) => strcmp($a['empId'].$a['date'], $b['empId'].$b['date']));

        return $out;
    }

    public function upsert(int $month, int $year, array $records): array
    {
        $map = $this->sheets->attendanceDaily($month, $year);
        $n = 0;
        foreach ($records as $r) {
            $e = up($r['empId'] ?? '');
            $d = s($r['date'] ?? '');
            if ($e === '' || $d === '') {
                continue;
            }
            $map[$e.'|'.$d] = strtoupper(s($r['status'] ?? 'P', 'P'));
            $n++;
        }
        $this->sheets->setAttendanceDaily($month, $year, $map);

        return ['upserted' => $n];
    }
}
