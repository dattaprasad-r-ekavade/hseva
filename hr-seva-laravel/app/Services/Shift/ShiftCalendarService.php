<?php

namespace App\Services\Shift;

use PDO;

class ShiftCalendarService
{
    public function __construct(
        private ShiftSupport $support,
        private ShiftRosterRepository $rosters,
    ) {}

    public function events(PDO $db, int $companyId, string $from, string $to, array $filters = []): array
    {
        $rows = $this->rosters->list($db, $companyId, $from, $to, $filters);
        $events = [];
        $summary = [];
        foreach ($rows as $r) {
            [$start, $end] = $this->support->dateTimeRange($r['rosterDate'], $r['startTime'], $r['endTime']);
            $day = $r['rosterDate'];
            if (! isset($summary[$day])) {
                $summary[$day] = ['date' => $day, 'totalScheduled' => 0, 'leaveCount' => 0, 'offCount' => 0, 'nightShiftCount' => 0];
            }
            $summary[$day]['totalScheduled']++;
            if (strtolower($r['shiftType']) === 'leave') {
                $summary[$day]['leaveCount']++;
            }
            if (strtolower($r['shiftType']) === 'off') {
                $summary[$day]['offCount']++;
            }
            if (strtolower($r['shiftCode']) === 'ns' || (string) $r['startTime'] >= '20:00') {
                $summary[$day]['nightShiftCount']++;
            }

            $events[] = [
                'eventId' => $r['id'], 'id' => $r['id'], 'title' => $r['employeeName'].' - '.$r['shiftCode'],
                'start' => $start ?? ($r['rosterDate'].'T00:00:00'), 'end' => $end ?? ($r['rosterDate'].'T23:59:59'),
                'allDay' => $start === null,
                'empId' => $r['empId'], 'employeeName' => $r['employeeName'], 'shiftCode' => $r['shiftCode'],
                'shiftName' => $r['shiftName'], 'shiftType' => $r['shiftType'], 'colorCode' => $r['colorCode'],
                'companyId' => $companyId, 'department' => $r['department'], 'notes' => $r['notes'],
                'status' => $r['status'], 'rosterDate' => $r['rosterDate'],
            ];
        }
        ksort($summary);

        return ['events' => $events, 'daySummary' => array_values($summary)];
    }
}
