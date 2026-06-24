<?php

namespace App\Services\Shift;

use PDO;

class ShiftAssignmentRepository
{
    public function __construct(private ShiftSupport $support) {}

    public function rows(PDO $db, int $companyId): array
    {
        $rows = $db->query('SELECT a.*, e.name AS employee_name, e.dept AS department, e.desig AS designation FROM employee_shift_assignments a LEFT JOIN employees e ON e.id=a.emp_id ORDER BY a.emp_id ASC')->fetchAll();

        return array_map(fn ($r) => [
            'id' => (int) $r['id'], 'companyId' => $companyId, 'empId' => (string) $r['emp_id'], 'employeeName' => s($r['employee_name'] ?? '', (string) $r['emp_id']),
            'department' => (string) ($r['department'] ?? ''), 'designation' => (string) ($r['designation'] ?? ''), 'defaultShiftCode' => (string) $r['default_shift_code'],
            'weeklyOffDay' => (string) $r['weekly_off_day'], 'effectiveFrom' => (string) $r['effective_from'], 'status' => (string) $r['status'],
            'createdAt' => (string) $r['created_at'], 'updatedAt' => (string) $r['updated_at'],
        ], $rows);
    }

    public function upsert(PDO $db, int $companyId, array $raw, bool $mustExist): array
    {
        $n = $this->normalize($raw);
        $id = $n['id'];
        $exists = false;
        if ($id > 0) {
            $q = $db->prepare('SELECT id FROM employee_shift_assignments WHERE id=?');
            $q->execute([$id]);
            $exists = (bool) $q->fetch();
        }
        if ($mustExist && ! $exists) {
            nf('Shift assignment not found');
        }

        $qe = $db->prepare('SELECT id FROM employees WHERE id=?');
        $qe->execute([$n['empId']]);
        if (! $qe->fetch()) {
            bad('Employee not found');
        }

        $qs = $db->prepare('SELECT id,status FROM shift_master WHERE shift_code=? LIMIT 1');
        $qs->execute([$n['defaultShiftCode']]);
        $srow = $qs->fetch();
        if (! $srow) {
            bad('defaultShiftCode not found in shift master');
        }
        if ((string) ($srow['status'] ?? '') !== 'Active') {
            bad('defaultShiftCode must be active');
        }

        $ts = now_iso();
        if ($exists) {
            $st = $db->prepare('UPDATE employee_shift_assignments SET emp_id=?,default_shift_code=?,weekly_off_day=?,effective_from=?,status=?,updated_at=? WHERE id=?');
            $st->execute([$n['empId'], $n['defaultShiftCode'], $n['weeklyOffDay'], $n['effectiveFrom'], $n['status'], $ts, $id]);
        } else {
            $st = $db->prepare('INSERT INTO employee_shift_assignments (emp_id,default_shift_code,weekly_off_day,effective_from,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?) ON CONFLICT(emp_id) DO UPDATE SET default_shift_code=excluded.default_shift_code,weekly_off_day=excluded.weekly_off_day,effective_from=excluded.effective_from,status=excluded.status,updated_at=excluded.updated_at');
            $st->execute([$n['empId'], $n['defaultShiftCode'], $n['weeklyOffDay'], $n['effectiveFrom'], $n['status'], $ts, $ts]);
            $id = (int) $db->lastInsertId();
            if ($id <= 0) {
                $q2 = $db->prepare('SELECT id FROM employee_shift_assignments WHERE emp_id=?');
                $q2->execute([$n['empId']]);
                $id = (int) ($q2->fetch()['id'] ?? 0);
            }
        }

        foreach ($this->rows($db, $companyId) as $r) {
            if ((int) $r['id'] === $id) {
                return $r;
            }
        }

        return ['id' => $id, 'companyId' => $companyId] + $n + ['createdAt' => $ts, 'updatedAt' => $ts];
    }

    public function delete(PDO $db, int $id): void
    {
        $st = $db->prepare('DELETE FROM employee_shift_assignments WHERE id=?');
        $st->execute([$id]);
        if ($st->rowCount() === 0) {
            nf('Shift assignment not found');
        }
    }

    public function normalize(array $raw): array
    {
        $empId = up($raw['empId'] ?? '');
        $defaultShiftCode = up($raw['defaultShiftCode'] ?? '');
        $weeklyOff = s($raw['weeklyOffDay'] ?? 'Sunday', 'Sunday');
        $effectiveFrom = $this->support->parseDate(s($raw['effectiveFrom'] ?? date('Y-m-d')), 'effectiveFrom');
        if ($empId === '' || $defaultShiftCode === '') {
            bad('empId and defaultShiftCode are required');
        }
        $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        if (! in_array($weeklyOff, $validDays, true)) {
            bad('weeklyOffDay must be Monday..Sunday');
        }

        return [
            'id' => (int) ($raw['id'] ?? 0),
            'empId' => $empId,
            'defaultShiftCode' => $defaultShiftCode,
            'weeklyOffDay' => $weeklyOff,
            'effectiveFrom' => $effectiveFrom,
            'status' => s($raw['status'] ?? 'Active', 'Active') === 'Inactive' ? 'Inactive' : 'Active',
        ];
    }
}
