<?php

namespace App\Services\Leaves;

class LeaveRepository
{
    public function list(?int $month = null, ?int $year = null, ?string $leaveType = null, ?string $status = null): array
    {
        $sql = 'SELECT * FROM leaves WHERE 1=1';
        $args = [];

        if ($month !== null) {
            $sql .= " AND CAST(strftime('%m', from_date) AS INTEGER)=?";
            $args[] = $month;
        }
        if ($year !== null) {
            $sql .= " AND CAST(strftime('%Y', from_date) AS INTEGER)=?";
            $args[] = $year;
        }
        if ($leaveType !== null && $leaveType !== '') {
            $sql .= ' AND leave_type=?';
            $args[] = up($leaveType);
        }
        if ($status !== null && $status !== '') {
            $sql .= ' AND status=?';
            $args[] = $status;
        }

        $sql .= ' ORDER BY from_date DESC,id DESC';
        $q = db()->prepare($sql);
        $q->execute($args);

        return array_map(fn ($r) => $this->payload($r), $q->fetchAll());
    }

    public function normLeave(array $raw): array
    {
        $x = [
            'empId' => up($raw['empId'] ?? ''),
            'empName' => s($raw['empName'] ?? ''),
            'fromDate' => s($raw['fromDate'] ?? ''),
            'toDate' => s($raw['toDate'] ?? ''),
            'leaveType' => up($raw['leaveType'] ?? ''),
            'reason' => s($raw['reason'] ?? ''),
            'days' => f($raw['days'] ?? 0),
            'dept' => s($raw['dept'] ?? ''),
            'desig' => s($raw['desig'] ?? ''),
            'company' => s($raw['company'] ?? ''),
            'status' => s($raw['status'] ?? 'Approved', 'Approved'),
            'halfDay' => s($raw['halfDay'] ?? 'No', 'No'),
            'markedBy' => s($raw['markedBy'] ?? 'Client HR', 'Client HR'),
            'id' => $raw['id'] ?? null,
        ];

        if ($x['empId'] === '' || $x['empName'] === '' || $x['fromDate'] === '' || $x['toDate'] === '' || $x['reason'] === '' || $x['days'] <= 0) {
            bad('Invalid leave data');
        }
        if (! in_array($x['leaveType'], ['CL', 'SL', 'EL', 'LOP'], true)) {
            bad('leaveType must be CL/SL/EL/LOP');
        }

        return $x;
    }

    public function upsert(array $raw, ?bool $mustExist = null): array
    {
        $n = $this->normLeave($raw);
        $id = $n['id'] !== null ? (int) $n['id'] : null;

        if ($mustExist === true && $id === null) {
            bad('leave id required');
        }

        $isNew = $id === null;

        if ($id !== null) {
            $q = db()->prepare('SELECT id FROM leaves WHERE id=?');
            $q->execute([$id]);
            if ($mustExist === true && ! $q->fetch()) {
                nf('Leave not found');
            }
        }

        $ts = now_iso();

        if ($id === null) {
            $st = db()->prepare(
                'INSERT INTO leaves (emp_id,emp_name,dept,desig,company,from_date,to_date,days,leave_type,reason,status,half_day,marked_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $st->execute([
                $n['empId'], $n['empName'], $n['dept'], $n['desig'], $n['company'],
                $n['fromDate'], $n['toDate'], $n['days'], $n['leaveType'], $n['reason'],
                $n['status'], $n['halfDay'], $n['markedBy'], $ts, $ts,
            ]);
            $id = (int) db()->lastInsertId();
        } else {
            $st = db()->prepare(
                'UPDATE leaves SET emp_id=?,emp_name=?,dept=?,desig=?,company=?,from_date=?,to_date=?,days=?,leave_type=?,reason=?,status=?,half_day=?,marked_by=?,updated_at=? WHERE id=?'
            );
            $st->execute([
                $n['empId'], $n['empName'], $n['dept'], $n['desig'], $n['company'],
                $n['fromDate'], $n['toDate'], $n['days'], $n['leaveType'], $n['reason'],
                $n['status'], $n['halfDay'], $n['markedBy'], $ts, $id,
            ]);
        }

        $n['id'] = $id;
        $n['__updatedAt'] = $ts;
        mail_leave_event(req_client_id(), (string) $id, $n, $isNew);

        return $n;
    }

    public function delete(int $id): void
    {
        $q = db()->prepare('SELECT emp_id, from_date, to_date, leave_type FROM leaves WHERE id=?');
        $q->execute([$id]);
        $row = $q->fetch();
        if (! $row) {
            nf('Leave not found');
        }

        $st = db()->prepare('DELETE FROM leaves WHERE id=?');
        $st->execute([$id]);
        attendance_unmark_leave(
            (string) $row['emp_id'],
            (string) $row['from_date'],
            (string) $row['to_date'],
            (string) $row['leave_type']
        );
    }

    public function summary(int $month, int $year): array
    {
        $q = db()->prepare(
            "SELECT emp_id AS empId, emp_name AS empName, SUM(CASE WHEN leave_type='CL' THEN days ELSE 0 END) AS clDays, SUM(CASE WHEN leave_type='SL' THEN days ELSE 0 END) AS slDays, SUM(CASE WHEN leave_type='EL' THEN days ELSE 0 END) AS elDays, SUM(CASE WHEN leave_type='LOP' THEN days ELSE 0 END) AS lopDays, SUM(days) AS totalDays FROM leaves WHERE CAST(strftime('%m', from_date) AS INTEGER)=? AND CAST(strftime('%Y', from_date) AS INTEGER)=? AND status='Approved' GROUP BY emp_id, emp_name ORDER BY emp_id ASC"
        );
        $q->execute([$month, $year]);

        return $q->fetchAll();
    }

    public function clear(): void
    {
        db()->exec('DELETE FROM leaves');
    }

    private function payload(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'empId' => $r['emp_id'],
            'empName' => $r['emp_name'],
            'dept' => $r['dept'],
            'desig' => $r['desig'],
            'company' => $r['company'],
            'fromDate' => $r['from_date'],
            'toDate' => $r['to_date'],
            'days' => (float) $r['days'],
            'leaveType' => $r['leave_type'],
            'reason' => $r['reason'],
            'status' => $r['status'],
            'halfDay' => $r['half_day'],
            'markedBy' => $r['marked_by'],
            '__updatedAt' => $r['updated_at'],
        ];
    }
}
