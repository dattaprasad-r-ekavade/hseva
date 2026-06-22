<?php

namespace App\Services\Shift;

use PDO;

class ShiftRosterRepository
{
    public function __construct(private ShiftSupport $support) {}

    public function list(PDO $db, int $companyId, string $start, string $end, array $filters = []): array
    {
        $where = ['r.roster_date BETWEEN ? AND ?'];
        $args = [$start, $end];
        $emp = up($filters['empId'] ?? '');
        if ($emp !== '') {
            $where[] = 'r.emp_id=?';
            $args[] = $emp;
        }
        $dep = s($filters['department'] ?? '');
        if ($dep !== '') {
            $where[] = 'e.dept=?';
            $args[] = $dep;
        }
        $des = s($filters['designation'] ?? '');
        if ($des !== '') {
            $where[] = 'e.desig=?';
            $args[] = $des;
        }
        $shiftCode = up($filters['shiftCode'] ?? '');
        if ($shiftCode !== '') {
            $where[] = 'r.shift_code=?';
            $args[] = $shiftCode;
        }

        $sql = 'SELECT r.*, e.name AS employee_name, e.dept AS department, e.desig AS designation,
      s.shift_name, s.shift_type, s.start_time, s.end_time, s.color_code
    FROM shift_rosters r
    LEFT JOIN employees e ON e.id=r.emp_id
    LEFT JOIN shift_master s ON s.shift_code=r.shift_code
    WHERE '.implode(' AND ', $where).' ORDER BY e.name ASC, r.emp_id ASC, r.roster_date ASC';
        $q = $db->prepare($sql);
        $q->execute($args);
        $rows = $q->fetchAll();

        return array_map(function ($r) use ($companyId) {
            return [
                'id' => (int) $r['id'], 'companyId' => $companyId, 'empId' => (string) $r['emp_id'], 'employeeName' => s($r['employee_name'] ?? '', (string) $r['emp_id']),
                'department' => (string) ($r['department'] ?? ''), 'designation' => (string) ($r['designation'] ?? ''), 'rosterDate' => (string) $r['roster_date'],
                'shiftCode' => (string) $r['shift_code'], 'shiftName' => (string) ($r['shift_name'] ?? ''), 'shiftType' => (string) ($r['shift_type'] ?? ''),
                'startTime' => ($r['start_time'] ?? null), 'endTime' => ($r['end_time'] ?? null), 'colorCode' => (string) ($r['color_code'] ?? '#0d6efd'),
                'status' => (string) $r['status'], 'notes' => (string) $r['notes'], 'createdBy' => (string) $r['created_by'], 'updatedBy' => (string) $r['updated_by'],
                'createdAt' => (string) $r['created_at'], 'updatedAt' => (string) $r['updated_at'],
            ];
        }, $rows);
    }

    public function upsertRows(PDO $db, array $rows, string $actor): array
    {
        $up = 0;
        $ts = now_iso();
        $st = $db->prepare('INSERT INTO shift_rosters (emp_id,roster_date,shift_code,status,notes,created_by,updated_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?) ON CONFLICT(emp_id,roster_date) DO UPDATE SET shift_code=excluded.shift_code,status=excluded.status,notes=excluded.notes,updated_by=excluded.updated_by,updated_at=excluded.updated_at');
        $qe = $db->prepare('SELECT id FROM employees WHERE id=?');
        $qs = $db->prepare('SELECT id,status,shift_type FROM shift_master WHERE shift_code=? LIMIT 1');
        $attMaps = [];
        $attChanged = [];
        $attSave = function (string $date, string $empId, bool $isWeeklyOff) use ($db, &$attMaps, &$attChanged): void {
            $m = (int) gmdate('n', strtotime($date.' 00:00:00 UTC'));
            $y = (int) gmdate('Y', strtotime($date.' 00:00:00 UTC'));
            $k = sprintf('attendance_daily_%04d-%02d', $y, $m);
            if (! array_key_exists($k, $attMaps)) {
                $q = $db->prepare('SELECT value FROM app_kv WHERE key=?');
                $q->execute([$k]);
                $r = $q->fetch();
                $map = $r ? json_decode((string) $r['value'], true) : [];
                $attMaps[$k] = is_array($map) ? $map : [];
                $attChanged[$k] = false;
            }
            $key = up($empId).'|'.$date;
            if ($isWeeklyOff) {
                if (($attMaps[$k][$key] ?? '') !== 'WO') {
                    $attMaps[$k][$key] = 'WO';
                    $attChanged[$k] = true;
                }

                return;
            }
            if (($attMaps[$k][$key] ?? '') === 'WO') {
                unset($attMaps[$k][$key]);
                $attChanged[$k] = true;
            }
        };
        foreach ($rows as $row) {
            $n = $this->normalizeRow((array) $row);
            $qe->execute([$n['empId']]);
            if (! $qe->fetch()) {
                continue;
            }
            $qs->execute([$n['shiftCode']]);
            $srow = $qs->fetch();
            if (! $srow || (string) ($srow['status'] ?? '') !== 'Active') {
                continue;
            }
            $st->execute([$n['empId'], $n['rosterDate'], $n['shiftCode'], $n['status'], $n['notes'], $actor, $actor, $ts, $ts]);
            $isWeeklyOff = strtoupper((string) $n['shiftCode']) === 'WO' || strtolower((string) ($srow['shift_type'] ?? '')) === 'off';
            $attSave((string) $n['rosterDate'], (string) $n['empId'], $isWeeklyOff);
            $up++;
        }
        foreach ($attMaps as $k => $map) {
            if (empty($attChanged[$k])) {
                continue;
            }
            $sv = $db->prepare('INSERT INTO app_kv (key,value,updated_at) VALUES (?,?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=excluded.updated_at');
            $sv->execute([$k, json_encode($map, JSON_UNESCAPED_UNICODE), now_iso()]);
        }

        return ['upserted' => $up];
    }

    public function deleteRow(PDO $db, string $empId, string $rosterDate): array
    {
        $empId = up($empId);
        $rosterDate = $this->support->parseDate($rosterDate, 'rosterDate');
        if ($empId === '') {
            bad('empId is required');
        }
        $st = $db->prepare('DELETE FROM shift_rosters WHERE emp_id=? AND roster_date=?');
        $st->execute([$empId, $rosterDate]);
        $deleted = $st->rowCount();

        $m = (int) gmdate('n', strtotime($rosterDate.' 00:00:00 UTC'));
        $y = (int) gmdate('Y', strtotime($rosterDate.' 00:00:00 UTC'));
        $k = sprintf('attendance_daily_%04d-%02d', $y, $m);
        $q = $db->prepare('SELECT value FROM app_kv WHERE key=?');
        $q->execute([$k]);
        $r = $q->fetch();
        $map = $r ? json_decode((string) $r['value'], true) : [];
        if (! is_array($map)) {
            $map = [];
        }
        $attKey = $empId.'|'.$rosterDate;
        if (($map[$attKey] ?? '') === 'WO') {
            unset($map[$attKey]);
            $sv = $db->prepare('INSERT INTO app_kv (key,value,updated_at) VALUES (?,?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=excluded.updated_at');
            $sv->execute([$k, json_encode($map, JSON_UNESCAPED_UNICODE), now_iso()]);
        }

        return ['deleted' => $deleted];
    }

    public function bulkDelete(PDO $db, array $rows): array
    {
        $deleted = 0;
        foreach ($rows as $row) {
            $empId = up((string) ($row['empId'] ?? ''));
            $rosterDate = s($row['rosterDate'] ?? '');
            if ($empId === '' || $rosterDate === '') {
                continue;
            }
            $res = $this->deleteRow($db, $empId, $rosterDate);
            $deleted += (int) ($res['deleted'] ?? 0);
        }

        return ['deleted' => $deleted];
    }

    public function weekStatusGet(PDO $db, string $weekStart, string $weekEnd): array
    {
        $q = $db->prepare('SELECT * FROM shift_roster_weeks WHERE week_start_date=? AND week_end_date=? LIMIT 1');
        $q->execute([$weekStart, $weekEnd]);
        $r = $q->fetch();
        if (! $r) {
            return ['weekStartDate' => $weekStart, 'weekEndDate' => $weekEnd, 'isLocked' => false, 'publishStatus' => 'Draft'];
        }

        return ['weekStartDate' => $weekStart, 'weekEndDate' => $weekEnd, 'isLocked' => ((int) $r['is_locked']) === 1, 'publishStatus' => (string) $r['publish_status']];
    }

    public function weekStatusSet(PDO $db, string $weekStart, string $weekEnd, bool $isLocked, string $publishStatus, string $actor): array
    {
        $ts = now_iso();
        if ($publishStatus !== 'Published') {
            $publishStatus = 'Draft';
        }
        $st = $db->prepare('INSERT INTO shift_roster_weeks (week_start_date,week_end_date,is_locked,publish_status,created_by,updated_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?) ON CONFLICT(week_start_date,week_end_date) DO UPDATE SET is_locked=excluded.is_locked,publish_status=excluded.publish_status,updated_by=excluded.updated_by,updated_at=excluded.updated_at');
        $st->execute([$weekStart, $weekEnd, $isLocked ? 1 : 0, $publishStatus, $actor, $actor, $ts, $ts]);
        $db->prepare('UPDATE shift_rosters SET status=?, updated_by=?, updated_at=? WHERE roster_date BETWEEN ? AND ?')->execute([$publishStatus, $actor, $ts, $weekStart, $weekEnd]);

        return $this->weekStatusGet($db, $weekStart, $weekEnd);
    }

    public function autofillWeek(PDO $db, string $weekStart, string $weekEnd, string $actor, array $filters = []): array
    {
        $days = $this->support->weekDays($weekStart);
        if (count($days) !== 7) {
            bad('Invalid weekStartDate');
        }
        $offShiftCode = 'WO';
        $qs = $db->query("SELECT shift_code FROM shift_master WHERE shift_type='Off' AND status='Active' ORDER BY id ASC LIMIT 1")->fetch();
        if ($qs) {
            $offShiftCode = (string) $qs['shift_code'];
        }

        $sql = "SELECT e.id, e.dept, e.desig, a.default_shift_code, a.weekly_off_day
    FROM employees e
    LEFT JOIN employee_shift_assignments a ON a.emp_id=e.id
    WHERE lower(e.status) <> 'inactive'";
        $args = [];
        $dep = s($filters['department'] ?? '');
        if ($dep !== '') {
            $sql .= ' AND e.dept=?';
            $args[] = $dep;
        }
        $des = s($filters['designation'] ?? '');
        if ($des !== '') {
            $sql .= ' AND e.desig=?';
            $args[] = $des;
        }
        $emp = up($filters['empId'] ?? '');
        if ($emp !== '') {
            $sql .= ' AND e.id=?';
            $args[] = $emp;
        }
        $q = $db->prepare($sql);
        $q->execute($args);
        $emps = $q->fetchAll();

        $rows = [];
        foreach ($emps as $e) {
            $default = up($e['default_shift_code'] ?? '');
            if ($default === '') {
                $default = 'GS';
            }
            $offDay = s($e['weekly_off_day'] ?? 'Sunday', 'Sunday');
            foreach ($days as $date) {
                $dayName = gmdate('l', strtotime($date.' 00:00:00 UTC'));
                $rows[] = [
                    'empId' => (string) $e['id'],
                    'rosterDate' => $date,
                    'shiftCode' => ($dayName === $offDay ? $offShiftCode : $default),
                    'status' => 'Draft',
                ];
            }
        }

        return $this->upsertRows($db, $rows, $actor) + ['generatedRows' => count($rows)];
    }

    public function copyPreviousWeek(PDO $db, string $weekStart, string $weekEnd, string $actor): array
    {
        $prevStart = gmdate('Y-m-d', strtotime($weekStart.' -7 day UTC'));
        $prevEnd = gmdate('Y-m-d', strtotime($weekEnd.' -7 day UTC'));
        $prev = $this->list($db, 0, $prevStart, $prevEnd, []);
        $rows = [];
        foreach ($prev as $r) {
            $newDate = gmdate('Y-m-d', strtotime($r['rosterDate'].' +7 day UTC'));
            $rows[] = ['empId' => $r['empId'], 'rosterDate' => $newDate, 'shiftCode' => $r['shiftCode'], 'status' => 'Draft', 'notes' => $r['notes'] ?? ''];
        }

        return $this->upsertRows($db, $rows, $actor) + ['copiedFromWeek' => $prevStart.' to '.$prevEnd, 'rows' => count($rows)];
    }

    public function myRoster(PDO $db, int $companyId, string $empId, string $from, string $to): array
    {
        $rows = $this->list($db, $companyId, $from, $to, ['empId' => $empId]);
        $today = gmdate('Y-m-d');
        $todayRow = null;
        foreach ($rows as $r) {
            if ($r['rosterDate'] === $today) {
                $todayRow = $r;
                break;
            }
        }

        return ['empId' => $empId, 'from' => $from, 'to' => $to, 'todayShift' => $todayRow, 'rows' => $rows];
    }

    public function normalizeRow(array $row): array
    {
        $empId = up($row['empId'] ?? '');
        $date = $this->support->parseDate(s($row['rosterDate'] ?? ''), 'rosterDate');
        $shiftCode = up($row['shiftCode'] ?? '');
        if ($empId === '' || $shiftCode === '') {
            bad('empId and shiftCode are required');
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'empId' => $empId,
            'rosterDate' => $date,
            'shiftCode' => $shiftCode,
            'status' => s($row['status'] ?? 'Draft', 'Draft') === 'Published' ? 'Published' : 'Draft',
            'notes' => s($row['notes'] ?? ''),
        ];
    }
}
