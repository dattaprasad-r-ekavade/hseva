<?php

namespace App\Services\Shift;

use PDO;

class ShiftDashboardService
{
    public function __construct(
        private ShiftAccess $access,
        private ShiftSchemaInstaller $schema,
        private ShiftRosterRepository $rosters,
    ) {}

    public function summary(array $companyIds): array
    {
        $today = gmdate('Y-m-d');
        $next7 = gmdate('Y-m-d', strtotime('+6 day UTC'));
        $totCompaniesUsing = 0;
        $activeShifts = 0;
        $assignedEmp = 0;
        $todayShifts = 0;
        $weeklyOffToday = 0;
        $leaveToday = 0;
        $nightShiftToday = 0;
        $missingRosters = 0;
        $withoutDefault = 0;
        $recentUpdates = [];
        $distByCompany = [];

        foreach ($companyIds as $cid) {
            $d = $this->access->dbForCompany((int) $cid);
            $this->schema->install($d);
            $hasUsage = (int) ($d->query('SELECT (SELECT COUNT(*) FROM shift_master)+(SELECT COUNT(*) FROM shift_rosters)+(SELECT COUNT(*) FROM employee_shift_assignments) AS c')->fetch()['c'] ?? 0) > 0;
            if ($hasUsage) {
                $totCompaniesUsing++;
            }
            $activeShifts += (int) ($d->query("SELECT COUNT(*) AS c FROM shift_master WHERE status='Active'")->fetch()['c'] ?? 0);
            $assignedEmp += (int) ($d->query('SELECT COUNT(DISTINCT emp_id) AS c FROM shift_rosters')->fetch()['c'] ?? 0);
            $todayRows = $this->rosters->list($d, (int) $cid, $today, $today, []);
            $todayShifts += count($todayRows);
            foreach ($todayRows as $r) {
                if (strtolower($r['shiftType']) === 'off') {
                    $weeklyOffToday++;
                }
                if (strtolower($r['shiftType']) === 'leave') {
                    $leaveToday++;
                }
                if (strtolower($r['shiftCode']) === 'ns' || (string) $r['startTime'] >= '20:00') {
                    $nightShiftToday++;
                }
            }
            $emap = $d->query("SELECT id FROM employees WHERE lower(status) <> 'inactive'")->fetchAll();
            $assignedMap = [];
            $q = $d->query("SELECT DISTINCT emp_id FROM employee_shift_assignments WHERE status='Active'")->fetchAll();
            foreach ($q as $a) {
                $assignedMap[up($a['emp_id'] ?? '')] = true;
            }
            foreach ($emap as $e) {
                if (empty($assignedMap[up($e['id'] ?? '')])) {
                    $withoutDefault++;
                }
            }
            $missing = $d->prepare("SELECT COUNT(*) AS c FROM employees e WHERE lower(e.status) <> 'inactive' AND NOT EXISTS (SELECT 1 FROM shift_rosters r WHERE r.emp_id=e.id AND r.roster_date BETWEEN ? AND ?)");
            $missing->execute([$today, $next7]);
            $missingRosters += (int) ($missing->fetch()['c'] ?? 0);
            $recent = $d->query('SELECT emp_id, roster_date, shift_code, updated_at FROM shift_rosters ORDER BY updated_at DESC LIMIT 5')->fetchAll();
            $cName = $this->access->companyName((int) $cid);
            foreach ($recent as $rr) {
                $recentUpdates[] = [
                    'companyId' => (int) $cid, 'companyName' => $cName, 'empId' => (string) $rr['emp_id'],
                    'rosterDate' => (string) $rr['roster_date'], 'shiftCode' => (string) $rr['shift_code'],
                    'updatedAt' => (string) $rr['updated_at'],
                ];
            }
            $q2 = $d->prepare("SELECT COALESCE(e.dept,'Unmapped') AS department, COUNT(*) AS c FROM shift_rosters r LEFT JOIN employees e ON e.id=r.emp_id WHERE r.roster_date=? GROUP BY COALESCE(e.dept,'Unmapped') ORDER BY c DESC");
            $q2->execute([$today]);
            foreach ($q2->fetchAll() as $dr) {
                $distByCompany[] = [
                    'companyId' => (int) $cid, 'companyName' => $cName,
                    'department' => (string) $dr['department'], 'count' => (int) $dr['c'],
                ];
            }
        }
        usort($recentUpdates, fn ($a, $b) => strcmp((string) $b['updatedAt'], (string) $a['updatedAt']));
        $recentUpdates = array_slice($recentUpdates, 0, 20);

        return [
            'today' => $today,
            'totals' => [
                'companiesUsingModule' => $totCompaniesUsing, 'activeShifts' => $activeShifts,
                'employeesAssignedInRoster' => $assignedEmp, 'todayShifts' => $todayShifts,
                'weeklyOffToday' => $weeklyOffToday, 'leaveToday' => $leaveToday,
                'nightShiftToday' => $nightShiftToday, 'upcomingConflictsOrMissingRosters' => $missingRosters,
                'employeesWithoutDefaultShift' => $withoutDefault,
            ],
            'todayShiftSummary' => ['date' => $today, 'total' => $todayShifts, 'off' => $weeklyOffToday, 'leave' => $leaveToday, 'night' => $nightShiftToday],
            'upcoming7DaysShiftSummary' => ['from' => $today, 'to' => $next7, 'missingRosters' => $missingRosters],
            'recentRosterUpdates' => $recentUpdates,
            'shiftDistributionByCompanyDepartment' => $distByCompany,
        ];
    }
}
