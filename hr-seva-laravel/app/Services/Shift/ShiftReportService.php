<?php

namespace App\Services\Shift;

use PDO;

class ShiftReportService
{
    public function __construct(
        private ShiftSupport $support,
        private ShiftAccess $access,
        private ShiftRosterRepository $rosters,
    ) {}

    public function attendanceReport(PDO $db, int $companyId, string $from, string $to): array
    {
        $companyName = $this->access->companyName($companyId);
        $rows = $this->rosters->list($db, $companyId, $from, $to, []);
        $leaveMap = $this->leaveDatesMap($db, $from, $to);
        $out = [];
        foreach ($rows as $r) {
            $att = $this->attendanceStatusOn($db, $r['rosterDate'], $r['empId']);
            $scheduledIn = $r['startTime'];
            $scheduledOut = $r['endTime'];
            $actualIn = null;
            $actualOut = null;
            $workHours = 0.0;
            if ($att === 'P' && $scheduledIn && $scheduledOut) {
                $workHours = round($this->support->durationMinutes((string) $scheduledIn, (string) $scheduledOut) / 60, 2);
            }
            $isLeave = ! empty($leaveMap[up($r['empId']).'|'.$r['rosterDate']]);
            $status = $att !== '' ? $att : ($isLeave ? 'LEAVE' : 'NA');
            $shiftMismatch = false;
            if (strtoupper($r['shiftType']) === 'OFF' && $att === 'P') {
                $shiftMismatch = true;
            }
            if (strtoupper($r['shiftType']) === 'WORKING' && in_array($att, ['WO', 'CL', 'SL', 'EL', 'LOP'], true)) {
                $shiftMismatch = true;
            }

            $out[] = [
                'date' => $r['rosterDate'], 'company' => $companyName, 'companyId' => $companyId,
                'empId' => $r['empId'], 'employeeName' => $r['employeeName'], 'shiftCode' => $r['shiftCode'],
                'shiftName' => $r['shiftName'], 'scheduledIn' => $scheduledIn, 'scheduledOut' => $scheduledOut,
                'actualIn' => $actualIn, 'actualOut' => $actualOut, 'workHours' => $workHours, 'status' => $status,
                'lateMark' => false, 'earlyExit' => false, 'overtime' => 0, 'shiftMismatch' => $shiftMismatch,
            ];
        }

        return $out;
    }

    public function attendanceReportCsv(array $rows): string
    {
        $lines = ['Date,Company,Employee ID,Employee Name,Shift Code,Shift Name,Scheduled In,Scheduled Out,Actual In,Actual Out,Work Hours,Status,Late Mark,Early Exit,Overtime,Shift Mismatch'];
        foreach ($rows as $r) {
            $lines[] = implode(',', array_map(
                static fn ($v) => '"'.str_replace('"', '""', (string) $v).'"',
                [
                    $r['date'], $r['company'], $r['empId'], $r['employeeName'], $r['shiftCode'], $r['shiftName'],
                    $r['scheduledIn'], $r['scheduledOut'], $r['actualIn'], $r['actualOut'], $r['workHours'], $r['status'],
                    $r['lateMark'] ? 'Yes' : 'No', $r['earlyExit'] ? 'Yes' : 'No', $r['overtime'], $r['shiftMismatch'] ? 'Yes' : 'No',
                ]
            ));
        }

        return implode("\n", $lines)."\n";
    }

    public function attendanceStatusOn(PDO $db, string $date, string $empId): string
    {
        $m = (int) gmdate('n', strtotime($date.' 00:00:00 UTC'));
        $y = (int) gmdate('Y', strtotime($date.' 00:00:00 UTC'));
        $key = sprintf('attendance_daily_%04d-%02d', $y, $m);
        $q = $db->prepare('SELECT value FROM app_kv WHERE key=?');
        $q->execute([$key]);
        $r = $q->fetch();
        if (! $r) {
            return '';
        }
        $map = json_decode((string) $r['value'], true);
        if (! is_array($map)) {
            return '';
        }

        return strtoupper((string) ($map[up($empId).'|'.$date] ?? ''));
    }

    public function leaveDatesMap(PDO $db, string $from, string $to): array
    {
        $q = $db->prepare("SELECT emp_id, from_date, to_date FROM leaves WHERE status='Approved' AND to_date>=? AND from_date<=?");
        $q->execute([$from, $to]);
        $out = [];
        foreach ($q->fetchAll() as $r) {
            $eid = up($r['emp_id'] ?? '');
            $s1 = strtotime((string) $r['from_date'].' 00:00:00 UTC');
            $e1 = strtotime((string) $r['to_date'].' 00:00:00 UTC');
            if ($eid === '' || $s1 === false || $e1 === false) {
                continue;
            }
            for ($t = $s1; $t <= $e1; $t += 86400) {
                $out[$eid.'|'.gmdate('Y-m-d', $t)] = true;
            }
        }

        return $out;
    }
}
