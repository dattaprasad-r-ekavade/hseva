<?php

namespace App\Services\Advances;

use PDO;

class AdvanceRepository
{
    public function rows(array $ctx, bool $outstandingOnly = false): array
    {
        $d = db();
        $empScope = $this->employeeScope($ctx);
        $sql = 'SELECT * FROM salary_advances';
        $params = [];
        if ($empScope !== '') {
            $sql .= ' WHERE emp_id=?';
            $params[] = $empScope;
        }
        $sql .= ' ORDER BY disbursed_on DESC, created_at DESC';
        $q = $d->prepare($sql);
        $q->execute($params);
        $out = [];
        foreach ($q->fetchAll() as $r) {
            $row = $this->payload($r);
            $row += $this->calcSummary($d, $row);
            if ($row['remainingBalance'] <= 0 && $row['status'] !== 'Closed') {
                $d->prepare("UPDATE salary_advances SET status='Closed', updated_at=? WHERE id=?")->execute([now_iso(), $row['id']]);
                $row['status'] = 'Closed';
            }
            if ($outstandingOnly && $row['remainingBalance'] <= 0) {
                continue;
            }
            $out[] = $row;
        }

        return $out;
    }

    public function create(array $payload): array
    {
        advance_manage_ctx();
        $clientId = req_client_id();
        $d = db();
        $empId = up($payload['empId'] ?? '');
        if ($empId === '') {
            bad('empId is required');
        }
        $emp = null;
        foreach (employees_all() as $e) {
            if (up($e['id'] ?? '') === $empId) {
                $emp = $e;
                break;
            }
        }
        if (! $emp) {
            nf('Employee not found');
        }
        $amount = round(f($payload['amount'] ?? 0), 2);
        if ($amount <= 0) {
            bad('amount must be greater than 0');
        }
        $disbursedOn = s($payload['disbursedOn'] ?? gmdate('Y-m-d'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $disbursedOn)) {
            bad('disbursedOn must be YYYY-MM-DD');
        }
        $ts = strtotime($disbursedOn.' 00:00:00 UTC');
        if ($ts === false) {
            bad('disbursedOn is invalid');
        }
        $startYear = (int) gmdate('Y', $ts);
        $startMonth = (int) gmdate('n', $ts);
        $eligibility = $this->eligibility($empId, $disbursedOn);
        $remainingEligible = round(f($eligibility['remainingEligible'] ?? 0), 2);
        if ($remainingEligible <= 0) {
            bad('No eligible attendance-based advance is available for the selected employee and date');
        }
        if ($amount > $remainingEligible) {
            bad('Advance amount cannot exceed the calculated salary on present attendance');
        }
        $repaymentType = 'full';
        $emiMonths = 1;
        $id = 'ADV-'.preg_replace('/[^A-Z0-9]/', '', $empId).'-'.time();
        $emiAmount = round($amount, 2);
        $actor = auth_actor_name();
        $now = now_iso();
        $notes = s($payload['notes'] ?? '');
        $st = $d->prepare('INSERT INTO salary_advances (id,emp_id,employee_name,amount,repayment_type,emi_months,emi_amount,disbursed_on,start_year,start_month,attendance_year,attendance_month,attendance_through_date,present_days,eligible_salary,monthly_gross,notes,status,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([$id, $empId, s($emp['name'] ?? $empId), $amount, $repaymentType, $emiMonths, $emiAmount, $disbursedOn, $startYear, $startMonth, (int) ($eligibility['year'] ?? $startYear), (int) ($eligibility['month'] ?? $startMonth), (string) ($eligibility['date'] ?? $disbursedOn), round(f($eligibility['presentDays'] ?? 0), 2), round(f($eligibility['eligibleSalary'] ?? 0), 2), round(f($eligibility['monthlyGross'] ?? 0), 2), $notes, 'Active', $actor, $now, $now]);
        $sched = $this->scheduleRows($amount, $repaymentType, $emiMonths, $startYear, $startMonth);
        $sd = $d->prepare('INSERT INTO advance_deductions (advance_id,emp_id,deduction_year,deduction_month,scheduled_amount,deducted_amount,balance_after,payroll_period,payroll_sheet_id,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $balance = $amount;
        foreach ($sched as $row) {
            $balance = round(max(0.0, $balance - f($row['scheduledAmount'] ?? 0)), 2);
            $sd->execute([$id, $empId, (int) $row['year'], (int) $row['month'], round(f($row['scheduledAmount'] ?? 0), 2), 0.0, $amount, '', '', 'Scheduled', $now, $now]);
        }
        invalidate_salary_dependent_sheets();
        $fresh = $this->fetchOne($d, $id) ?? ['id' => $id];
        mail_advance_event($clientId, $fresh);

        return $fresh;
    }

    public function eligibility(string $empId, string $asOfDate): array
    {
        $empId = up($empId);
        if ($empId === '') {
            bad('empId is required');
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOfDate)) {
            bad('date must be YYYY-MM-DD');
        }
        $ts = strtotime($asOfDate.' 00:00:00 UTC');
        if ($ts === false) {
            bad('date is invalid');
        }
        $emp = null;
        foreach (employees_all() as $e) {
            if (up($e['id'] ?? '') === $empId) {
                $emp = $e;
                break;
            }
        }
        if (! $emp) {
            nf('Employee not found');
        }
        $year = (int) gmdate('Y', $ts);
        $month = (int) gmdate('n', $ts);
        $day = (int) gmdate('j', $ts);
        $dim = (int) cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $daily = kv_get(att_month_key($month, $year), []);
        if (! is_array($daily)) {
            $daily = [];
        }
        $presentDays = 0.0;
        for ($d = 1; $d <= $day; $d++) {
            $iso = sprintf('%04d-%02d-%02d', $year, $month, $d);
            if ($this->attendanceStatusForDay($daily, $empId, $iso) === 'P') {
                $presentDays += 1.0;
            }
        }
        $monthlyGross = $this->effectiveMonthlyGross($emp);
        $perDaySalary = $dim > 0 ? round($monthlyGross / $dim, 2) : 0.0;
        $eligibleSalary = round($perDaySalary * $presentDays, 2);
        $existingMonthAdvance = $this->existingMonthTotal(db(), $empId, $year, $month);
        $remainingEligible = round(max(0.0, $eligibleSalary - $existingMonthAdvance), 2);

        return [
            'empId' => $empId,
            'employeeName' => (string) ($emp['name'] ?? $empId),
            'date' => $asOfDate,
            'year' => $year,
            'month' => $month,
            'daysInMonth' => $dim,
            'daysConsidered' => $day,
            'presentDays' => round($presentDays, 2),
            'monthlyGross' => round($monthlyGross, 2),
            'perDaySalary' => round($perDaySalary, 2),
            'eligibleSalary' => round($eligibleSalary, 2),
            'existingMonthAdvance' => round($existingMonthAdvance, 2),
            'remainingEligible' => round($remainingEligible, 2),
            'attendanceRule' => 'present_only',
        ];
    }

    public function historyRows(array $ctx): array
    {
        $d = db();
        $empScope = $this->employeeScope($ctx);
        $sql = 'SELECT d.*, a.employee_name, a.amount AS advance_amount, a.repayment_type FROM advance_deductions d JOIN salary_advances a ON a.id=d.advance_id';
        $params = [];
        if ($empScope !== '') {
            $sql .= ' WHERE d.emp_id=?';
            $params[] = $empScope;
        }
        $sql .= ' ORDER BY d.deduction_year DESC, d.deduction_month DESC, d.id DESC';
        $q = $d->prepare($sql);
        $q->execute($params);
        $rows = [];
        foreach ($q->fetchAll() as $r) {
            $rows[] = [
                'id' => (int) $r['id'],
                'advanceId' => (string) $r['advance_id'],
                'empId' => (string) $r['emp_id'],
                'employeeName' => (string) $r['employee_name'],
                'period' => $this->monthPeriod((int) $r['deduction_year'], (int) $r['deduction_month']),
                'scheduledAmount' => round(f($r['scheduled_amount'] ?? 0), 2),
                'deductedAmount' => round(f($r['deducted_amount'] ?? 0), 2),
                'balanceAfter' => round(f($r['balance_after'] ?? 0), 2),
                'status' => (string) $r['status'],
                'payrollPeriod' => (string) ($r['payroll_period'] ?? ''),
                'advanceAmount' => round(f($r['advance_amount'] ?? 0), 2),
                'repaymentType' => (string) ($r['repayment_type'] ?? 'full'),
                'updatedAt' => (string) ($r['updated_at'] ?? ''),
            ];
        }

        return $rows;
    }

    public function fetchOne(PDO $d, string $id): ?array
    {
        $q = $d->prepare('SELECT * FROM salary_advances WHERE id=? LIMIT 1');
        $q->execute([$id]);
        $r = $q->fetch();
        if (! $r) {
            return null;
        }
        $row = $this->payload($r);

        return $row + $this->calcSummary($d, $row);
    }

    public function delete(string $id): void
    {
        advance_manage_ctx();
        $id = s($id);
        if ($id === '') {
            bad('Invalid advance id');
        }
        $d = db();
        $row = $this->fetchOne($d, $id);
        if (! $row) {
            nf('Advance not found');
        }
        if (round(f($row['deductedAmount'] ?? 0), 2) > 0) {
            bad('Advance cannot be deleted after payroll deduction has started');
        }
        $d->prepare('DELETE FROM advance_deductions WHERE advance_id=?')->execute([$id]);
        $q = $d->prepare('DELETE FROM salary_advances WHERE id=?');
        $q->execute([$id]);
        if ($q->rowCount() <= 0) {
            nf('Advance not found');
        }
        invalidate_salary_dependent_sheets();
    }

    public function payrollApply(PDO $d, string $empId, int $month, int $year, float $maxAvailable, string $payrollSheetId = ''): array
    {
        $empId = up($empId);
        if ($empId === '' || $maxAvailable <= 0) {
            return ['amount' => 0.0, 'items' => []];
        }
        $q = $d->prepare("SELECT d.id, d.advance_id, d.scheduled_amount, a.amount AS advance_amount FROM advance_deductions d JOIN salary_advances a ON a.id=d.advance_id WHERE d.emp_id=? AND d.deduction_year=? AND d.deduction_month=? AND a.status IN ('Active','Closed') ORDER BY a.disbursed_on ASC, d.id ASC");
        $q->execute([$empId, $year, $month]);
        $rows = $q->fetchAll();
        if (! $rows) {
            return ['amount' => 0.0, 'items' => []];
        }
        $sumPrev = $d->prepare('SELECT COALESCE(SUM(deducted_amount),0) AS deducted FROM advance_deductions WHERE advance_id=? AND ((deduction_year < ?) OR (deduction_year = ? AND deduction_month < ?))');
        $upd = $d->prepare('UPDATE advance_deductions SET deducted_amount=?, balance_after=?, payroll_period=?, payroll_sheet_id=?, status=?, updated_at=? WHERE id=?');
        $advanceUpd = $d->prepare('UPDATE salary_advances SET status=?, updated_at=? WHERE id=?');
        $left = round($maxAvailable, 2);
        $total = 0.0;
        $items = [];
        foreach ($rows as $r) {
            $advanceId = (string) $r['advance_id'];
            $sumPrev->execute([$advanceId, $year, $year, $month]);
            $prevDeducted = round(f(($sumPrev->fetch() ?: [])['deducted'] ?? 0), 2);
            $advanceAmount = round(f($r['advance_amount'] ?? 0), 2);
            $remainingBefore = round(max(0.0, $advanceAmount - $prevDeducted), 2);
            $scheduled = round(min($remainingBefore, f($r['scheduled_amount'] ?? 0)), 2);
            $deducted = round(min($left, $scheduled), 2);
            $balanceAfter = round(max(0.0, $remainingBefore - $deducted), 2);
            $status = $deducted <= 0 ? 'Scheduled' : ($balanceAfter <= 0 ? 'Deducted' : ($deducted < $scheduled ? 'Partial' : 'Deducted'));
            $upd->execute([$deducted, $balanceAfter, $this->monthPeriod($year, $month), $payrollSheetId, $status, now_iso(), (int) $r['id']]);
            $advanceUpd->execute([$balanceAfter <= 0 ? 'Closed' : 'Active', now_iso(), $advanceId]);
            if ($deducted > 0) {
                $total = round($total + $deducted, 2);
                $left = round(max(0.0, $left - $deducted), 2);
                $items[] = ['advanceId' => $advanceId, 'amount' => $deducted];
            }
        }

        return ['amount' => $total, 'items' => $items];
    }

    public function outstandingForEmployee(PDO $d, string $empId, string $asOfDate = ''): array
    {
        $empId = up($empId);
        if ($empId === '') {
            return ['amount' => 0.0, 'items' => []];
        }
        $sql = 'SELECT * FROM salary_advances WHERE emp_id=?';
        $params = [$empId];
        if ($asOfDate !== '') {
            $sql .= ' AND disbursed_on<=?';
            $params[] = $asOfDate;
        }
        $sql .= ' ORDER BY disbursed_on ASC, created_at ASC';
        $q = $d->prepare($sql);
        $q->execute($params);
        $items = [];
        $total = 0.0;
        foreach ($q->fetchAll() as $r) {
            $row = $this->payload($r);
            $summary = $this->calcSummary($d, $row);
            $remaining = round(f($summary['remainingBalance'] ?? 0), 2);
            if ($remaining <= 0) {
                continue;
            }
            $items[] = [
                'advanceId' => (string) ($row['id'] ?? ''),
                'disbursedOn' => (string) ($row['disbursedOn'] ?? ''),
                'remainingAmount' => $remaining,
            ];
            $total = round($total + $remaining, 2);
        }

        return ['amount' => $total, 'items' => $items];
    }

    public function employeeScope(array $ctx): string
    {
        $role = strtolower((string) ($ctx['role'] ?? ''));
        if ($role !== 'employee') {
            return '';
        }

        return up($ctx['empId'] ?? '');
    }

    private function payload(array $r): array
    {
        return [
            'id' => (string) $r['id'],
            'empId' => (string) $r['emp_id'],
            'employeeName' => (string) $r['employee_name'],
            'amount' => round(f($r['amount'] ?? 0), 2),
            'repaymentType' => strtolower((string) $r['repayment_type']) === 'emi' ? 'emi' : 'full',
            'emiMonths' => (int) ($r['emi_months'] ?? 1),
            'emiAmount' => round(f($r['emi_amount'] ?? 0), 2),
            'disbursedOn' => (string) $r['disbursed_on'],
            'startYear' => (int) ($r['start_year'] ?? 0),
            'startMonth' => (int) ($r['start_month'] ?? 0),
            'attendanceYear' => (int) ($r['attendance_year'] ?? 0),
            'attendanceMonth' => (int) ($r['attendance_month'] ?? 0),
            'attendanceThroughDate' => (string) ($r['attendance_through_date'] ?? ''),
            'presentDays' => round(f($r['present_days'] ?? 0), 2),
            'eligibleSalary' => round(f($r['eligible_salary'] ?? 0), 2),
            'monthlyGross' => round(f($r['monthly_gross'] ?? 0), 2),
            'notes' => (string) ($r['notes'] ?? ''),
            'status' => (string) ($r['status'] ?? 'Active'),
            'createdBy' => (string) ($r['created_by'] ?? ''),
            'createdAt' => (string) ($r['created_at'] ?? ''),
            'updatedAt' => (string) ($r['updated_at'] ?? ''),
        ];
    }

    private function calcSummary(PDO $d, array $advance): array
    {
        $qid = (string) ($advance['id'] ?? '');
        $amount = f($advance['amount'] ?? 0);
        $q = $d->prepare("SELECT COALESCE(SUM(deducted_amount),0) AS deducted, MIN(CASE WHEN status<>'Deducted' AND balance_after>0 THEN (deduction_year*100 + deduction_month) ELSE NULL END) AS next_code FROM advance_deductions WHERE advance_id=?");
        $q->execute([$qid]);
        $r = $q->fetch() ?: [];
        $deducted = round(f($r['deducted'] ?? 0), 2);
        $remaining = round(max(0.0, $amount - $deducted), 2);
        $nextCode = (int) ($r['next_code'] ?? 0);
        $nextDue = '';
        if ($nextCode > 0) {
            $nextYear = (int) floor($nextCode / 100);
            $nextMonth = $nextCode % 100;
            $nextDue = $this->monthPeriod($nextYear, $nextMonth);
        }

        return ['deductedAmount' => $deducted, 'remainingBalance' => $remaining, 'nextDuePeriod' => $nextDue];
    }

    private function attendanceStatusForDay(array $daily, string $empId, string $isoDate): string
    {
        return strtoupper((string) ($daily[$empId.'|'.$isoDate] ?? ''));
    }

    private function effectiveMonthlyGross(array $emp): float
    {
        $eid = up($emp['id'] ?? '');
        $ctrl = control_get();
        $ov = ovr_all();
        $o = $ov[$eid] ?? [];
        $gross = (isset($o['gross']) && $o['gross'] !== null) ? f($o['gross']) : 0.0;
        $ctc = (isset($o['ctc']) && $o['ctc'] !== null) ? f($o['ctc']) : 0.0;
        $masterCtc = f($emp['baseCtc'] ?? 0);
        $base = $gross > 0 ? $gross : ($ctc > 0 ? $ctc : ($masterCtc > 0 ? $masterCtc : 25000));
        $parts = split_ctc($base, $ctrl);

        return round(f($parts['gross'] ?? 0), 2);
    }

    private function existingMonthTotal(PDO $d, string $empId, int $year, int $month): float
    {
        $q = $d->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM salary_advances WHERE emp_id=? AND attendance_year=? AND attendance_month=?');
        $q->execute([$empId, $year, $month]);
        $row = $q->fetch() ?: [];

        return round(f($row['total'] ?? 0), 2);
    }

    private function scheduleRows(float $amount, string $repaymentType, int $emiMonths, int $startYear, int $startMonth): array
    {
        $rows = [];
        $periods = $repaymentType === 'emi' ? max(1, $emiMonths) : 1;
        $baseAmt = $periods > 0 ? round($amount / $periods, 2) : round($amount, 2);
        $acc = 0.0;
        for ($i = 0; $i < $periods; $i++) {
            $p = advance_next_period($startYear, $startMonth, $i);
            $scheduled = ($i === $periods - 1) ? round($amount - $acc, 2) : $baseAmt;
            $acc = round($acc + $scheduled, 2);
            $rows[] = ['year' => $p['year'], 'month' => $p['month'], 'scheduledAmount' => $scheduled];
        }

        return $rows;
    }

    private function monthPeriod(int $year, int $month): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }
}
