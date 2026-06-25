<?php

namespace App\Services\Employees;

class EmployeeRepository
{
    public function all(): array
    {
        $rows = db()->query('SELECT * FROM employees ORDER BY id ASC')->fetchAll();

        return array_map(fn ($r) => $this->payload($r), $rows);
    }

    public function isActive(array $emp): bool
    {
        return strtolower(trim((string) ($emp['status'] ?? 'active'))) !== 'inactive';
    }

    public function activeAll(): array
    {
        return array_values(array_filter($this->all(), fn ($emp) => $this->isActive($emp)));
    }

    public function normEmp(array $raw): array
    {
        $id = up($raw['id'] ?? '');
        $name = s($raw['name'] ?? '');
        if ($id === '' || $name === '') {
            bad('Employee id and name are required');
        }

        return [
            'id' => $id,
            'name' => $name,
            'status' => s($raw['status'] ?? 'Active', 'Active'),
            'dept' => s($raw['dept'] ?? ''),
            'desig' => s($raw['desig'] ?? ''),
            'type' => s($raw['type'] ?? 'Full-time', 'Full-time'),
            'mobile' => s($raw['mobile'] ?? ''),
            'email' => s($raw['email'] ?? ''),
            'doj' => s($raw['doj'] ?? ''),
            'pf' => s($raw['pf'] ?? 'Yes', 'Yes'),
            'uan' => s($raw['uan'] ?? ''),
            'esi' => s($raw['esi'] ?? 'Yes', 'Yes'),
            'esiNo' => s($raw['esiNo'] ?? ''),
            'pfNo' => s($raw['pfNo'] ?? ''),
            'bankName' => s($raw['bankName'] ?? ''),
            'bankAc' => s($raw['bankAc'] ?? ''),
            'ifsc' => s($raw['ifsc'] ?? ''),
            'aadharNo' => s($raw['aadharNo'] ?? ''),
            'panCard' => s($raw['panCard'] ?? ''),
            'address' => s($raw['address'] ?? ''),
            'baseCtc' => f($raw['baseCtc'] ?? 0),
        ];
    }

    public function upsert(array $raw, ?bool $mustExist = null): array
    {
        $n = $this->normEmp($raw);
        $q = db()->prepare('SELECT id, base_ctc FROM employees WHERE id=?');
        $q->execute([$n['id']]);
        $old = $q->fetch();
        $exists = (bool) $old;

        if ($mustExist === true && ! $exists) {
            nf('Employee not found');
        }
        if ($mustExist === false && $exists) {
            j(['detail' => 'Employee id already exists'], 409);
        }

        $ts = now_iso();
        $st = db()->prepare(
            'INSERT INTO employees (id,name,status,dept,desig,type,mobile,email,doj,pf,uan,esi,esi_no,pf_no,bank_name,bank_ac,ifsc,aadhar_no,pan_card,address,base_ctc,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON CONFLICT(id) DO UPDATE SET name=excluded.name,status=excluded.status,dept=excluded.dept,desig=excluded.desig,type=excluded.type,mobile=excluded.mobile,email=excluded.email,doj=excluded.doj,pf=excluded.pf,uan=excluded.uan,esi=excluded.esi,esi_no=excluded.esi_no,pf_no=excluded.pf_no,bank_name=excluded.bank_name,bank_ac=excluded.bank_ac,ifsc=excluded.ifsc,aadhar_no=excluded.aadhar_no,pan_card=excluded.pan_card,address=excluded.address,base_ctc=excluded.base_ctc,updated_at=excluded.updated_at'
        );
        $st->execute([
            $n['id'], $n['name'], $n['status'], $n['dept'], $n['desig'], $n['type'],
            $n['mobile'], $n['email'], $n['doj'], $n['pf'], $n['uan'], $n['esi'],
            $n['esiNo'], $n['pfNo'], $n['bankName'], $n['bankAc'], $n['ifsc'],
            $n['aadharNo'], $n['panCard'], $n['address'], $n['baseCtc'], $ts, $ts,
        ]);

        $oldBase = $exists ? f($old['base_ctc'] ?? 0) : null;
        if (! $exists || $oldBase === null || abs($oldBase - f($n['baseCtc'])) > 0.0001) {
            invalidate_salary_dependent_sheets();
        }

        $row = $n + ['__updatedAt' => $ts];
        mail_employee_event(req_client_id(), (string) $n['id'], $row, ! $exists);

        return $row;
    }

    public function delete(string $id): void
    {
        $st = db()->prepare('DELETE FROM employees WHERE id=?');
        $st->execute([up($id)]);
        if ($st->rowCount() === 0) {
            nf('Employee not found');
        }
        invalidate_salary_dependent_sheets();
    }

    public function clear(): void
    {
        db()->exec('DELETE FROM employees');
        invalidate_salary_dependent_sheets();
    }

    private function payload(array $r): array
    {
        return [
            'id' => $r['id'],
            'name' => $r['name'],
            'status' => $r['status'],
            'dept' => $r['dept'],
            'desig' => $r['desig'],
            'type' => $r['type'],
            'mobile' => $r['mobile'],
            'email' => $r['email'],
            'doj' => $r['doj'],
            'pf' => $r['pf'],
            'uan' => $r['uan'],
            'esi' => $r['esi'],
            'esiNo' => $r['esi_no'],
            'pfNo' => $r['pf_no'],
            'bankName' => $r['bank_name'],
            'bankAc' => $r['bank_ac'],
            'ifsc' => $r['ifsc'],
            'aadharNo' => $r['aadhar_no'],
            'panCard' => $r['pan_card'],
            'address' => $r['address'],
            'baseCtc' => (float) ($r['base_ctc'] ?? 0),
            '__updatedAt' => $r['updated_at'],
        ];
    }
}
