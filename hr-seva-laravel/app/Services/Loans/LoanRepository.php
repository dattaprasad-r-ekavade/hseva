<?php

namespace App\Services\Loans;

use PDO;

class LoanRepository
{
    public function rows(): array
    {
        loan_view_ctx();
        $d = db();
        $rows = $d->query('SELECT * FROM loans ORDER BY created_at DESC, id DESC')->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $summary = $this->summary($d, (string) $r['id']);
            $payload = $this->payload($r + ['paid_amount' => $summary['paidAmount']]);
            $payload['status'] = $payload['balanceAmount'] <= 0 ? 'Closed' : ($summary['paidAmount'] > 0 ? 'Active' : $payload['status']);
            $payload['nextMonth'] = $summary['nextMonth'];
            $out[] = $payload;
        }

        return $out;
    }

    public function createOrUpdate(array $payload, ?string $existingId = null): array
    {
        loan_manage_ctx();
        $d = db();
        $isEdit = $existingId !== null && trim($existingId) !== '';
        $loanId = $isEdit ? s($existingId) : '';
        $existing = null;
        $lockedExisting = false;
        if ($isEdit) {
            $existing = $this->fetchOne($d, $loanId);
            if (! $existing) {
                nf('Loan not found');
            }
            $lockedExisting = array_reduce($existing['historyRows'] ?? [], fn ($carry, $row) => $carry || f($row['deductedAmount'] ?? 0) > 0, false);
        }
        $empId = up($payload['empId'] ?? ($existing['empId'] ?? ''));
        if ($empId === '') {
            bad('empId is required');
        }
        $snap = $this->employeeSnapshot($empId);
        $loanType = s($payload['loanType'] ?? ($existing['loanType'] ?? ''));
        if ($loanType === '') {
            bad('loanType is required');
        }
        $requestedAmount = round(f($payload['requestedAmount'] ?? ($existing['requestedAmount'] ?? 0)), 2);
        if ($requestedAmount <= 0) {
            bad('requestedAmount must be greater than 0');
        }
        $reason = s($payload['reason'] ?? ($existing['reason'] ?? ''));
        if ($reason === '') {
            bad('reason is required');
        }
        $requestDate = s($payload['requestDate'] ?? ($existing['requestDate'] ?? gmdate('Y-m-d')), gmdate('Y-m-d'));
        $requiredDate = s($payload['requiredDate'] ?? ($existing['requiredDate'] ?? ''), '');
        if ($requiredDate === '') {
            bad('requiredDate is required');
        }
        $repaymentType = strtolower(s($payload['repaymentType'] ?? ($existing['repaymentType'] ?? 'one_time'), 'one_time'));
        if (! in_array($repaymentType, ['one_time', 'emi'], true)) {
            bad('repaymentType must be one_time or emi');
        }
        $emiStartPeriod = s($payload['emiStartPeriod'] ?? ($existing['emiStartPeriod'] ?? ''), '');
        $emiStartYear = (int) ($payload['emiStartYear'] ?? ($existing['emiStartYear'] ?? 0));
        $emiStartMonth = (int) ($payload['emiStartMonth'] ?? ($existing['emiStartMonth'] ?? 0));
        if ($emiStartPeriod !== '' && preg_match('/^(\d{4})-(\d{2})$/', $emiStartPeriod, $m)) {
            $emiStartYear = (int) $m[1];
            $emiStartMonth = (int) $m[2];
        }
        if ($emiStartYear < 2000 || $emiStartMonth < 1 || $emiStartMonth > 12) {
            bad('emi start month is required');
        }
        $emiAmount = round(f($payload['emiAmount'] ?? ($existing['emiAmount'] ?? 0)), 2);
        $installmentCount = (int) ($payload['installmentCount'] ?? ($existing['installmentCount'] ?? 1));
        if ($repaymentType === 'one_time') {
            $installmentCount = 1;
            $emiAmount = $requestedAmount;
        } else {
            if ($emiAmount <= 0) {
                bad('emiAmount must be greater than 0');
            }
            if ($installmentCount <= 0) {
                $installmentCount = (int) ceil($requestedAmount / $emiAmount);
            }
        }
        $remarks = s($payload['remarks'] ?? ($existing['remarks'] ?? ''));
        $status = s($payload['status'] ?? ($existing['status'] ?? 'Active'), 'Active');
        if ($lockedExisting) {
            if ($requestedAmount !== round(f($existing['requestedAmount'] ?? 0), 2)) {
                bad('Cannot change loan amount after deductions have started');
            }
            if ($repaymentType !== strtolower(s($existing['repaymentType'] ?? 'one_time'))) {
                bad('Cannot change repayment type after deductions have started');
            }
            if ($emiAmount !== round(f($existing['emiAmount'] ?? 0), 2)) {
                bad('Cannot change EMI amount after deductions have started');
            }
            if ($installmentCount !== (int) ($existing['installmentCount'] ?? 1)) {
                bad('Cannot change installment count after deductions have started');
            }
            if ($emiStartYear !== (int) ($existing['emiStartYear'] ?? 0) || $emiStartMonth !== (int) ($existing['emiStartMonth'] ?? 0)) {
                bad('Cannot change EMI start month after deductions have started');
            }
        }
        $id = $isEdit ? $loanId : 'LOAN-'.preg_replace('/[^A-Z0-9]/', '', $empId).'-'.time();
        $now = now_iso();
        $actor = auth_actor_name();
        if ($isEdit) {
            $st = $d->prepare('UPDATE loans SET emp_id=?, employee_name=?, dept=?, designation=?, property_branch=?, loan_type=?, requested_amount=?, reason=?, request_date=?, required_date=?, repayment_type=?, emi_start_year=?, emi_start_month=?, emi_amount=?, installment_count=?, remarks=?, status=?, updated_at=? WHERE id=?');
            $st->execute([$empId, $snap['employeeName'], $snap['dept'], $snap['designation'], $snap['propertyBranch'], $loanType, $requestedAmount, $reason, $requestDate, $requiredDate, $repaymentType, $emiStartYear, $emiStartMonth, $emiAmount, $installmentCount, $remarks, $status, $now, $id]);
            if (! $lockedExisting) {
                $d->prepare('DELETE FROM loan_deductions WHERE loan_id=?')->execute([$id]);
            }
        } else {
            $st = $d->prepare('INSERT INTO loans (id,emp_id,employee_name,dept,designation,property_branch,loan_type,requested_amount,reason,request_date,required_date,repayment_type,emi_start_year,emi_start_month,emi_amount,installment_count,remarks,status,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([$id, $empId, $snap['employeeName'], $snap['dept'], $snap['designation'], $snap['propertyBranch'], $loanType, $requestedAmount, $reason, $requestDate, $requiredDate, $repaymentType, $emiStartYear, $emiStartMonth, $emiAmount, $installmentCount, $remarks, $status, $actor, $now, $now]);
        }
        if (! $lockedExisting) {
            $sched = $this->scheduleRows($requestedAmount, $repaymentType, $emiAmount, $installmentCount, $emiStartYear, $emiStartMonth);
            $sd = $d->prepare('INSERT INTO loan_deductions (loan_id,emp_id,deduction_year,deduction_month,scheduled_amount,deducted_amount,balance_after,payroll_period,payroll_sheet_id,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $remaining = $requestedAmount;
            foreach ($sched as $row) {
                $scheduled = round(f($row['scheduledAmount'] ?? 0), 2);
                $remaining = round(max(0.0, $remaining - $scheduled), 2);
                $sd->execute([$id, $empId, (int) $row['year'], (int) $row['month'], $scheduled, 0.0, $requestedAmount, '', '', 'Scheduled', $now, $now]);
            }
        }
        invalidate_salary_dependent_sheets();

        return $this->fetchOne($d, $id) ?? ['id' => $id];
    }

    public function fetchOne(PDO $d, string $id): ?array
    {
        $q = $d->prepare('SELECT * FROM loans WHERE id=? LIMIT 1');
        $q->execute([$id]);
        $row = $q->fetch();
        if (! $row) {
            return null;
        }
        $summary = $this->summary($d, (string) $row['id']);
        $payload = $this->payload($row + ['paid_amount' => $summary['paidAmount']]);
        $history = $this->deductionHistoryRows($d, (string) $row['id']);
        $status = $payload['balanceAmount'] <= 0 ? 'Closed' : ($summary['paidAmount'] > 0 ? 'Active' : $payload['status']);

        return $payload + ['status' => $status, 'nextMonth' => $summary['nextMonth'], 'historyRows' => $history];
    }

    public function delete(string $id): void
    {
        loan_delete_ctx();
        $d = db();
        $row = $this->fetchOne($d, $id);
        if (! $row) {
            nf('Loan not found');
        }
        foreach (($row['historyRows'] ?? []) as $hist) {
            if (f($hist['deductedAmount'] ?? 0) > 0) {
                bad('Cannot delete loan after deductions have started');
            }
        }
        $d->prepare('DELETE FROM loan_deductions WHERE loan_id=?')->execute([$id]);
        $q = $d->prepare('DELETE FROM loans WHERE id=?');
        $q->execute([$id]);
        if ($q->rowCount() <= 0) {
            nf('Loan not found');
        }
        invalidate_salary_dependent_sheets();
    }

    public function payrollApply(PDO $d, string $empId, int $month, int $year, float $maxAvailable, string $payrollSheetId = ''): array
    {
        $empId = up($empId);
        if ($empId === '' || $maxAvailable <= 0) {
            return ['amount' => 0.0, 'items' => []];
        }
        $q = $d->prepare("SELECT d.id, d.loan_id, d.scheduled_amount, l.requested_amount FROM loan_deductions d JOIN loans l ON l.id=d.loan_id WHERE d.emp_id=? AND d.deduction_year=? AND d.deduction_month=? AND l.status IN ('Active','Closed') ORDER BY l.created_at ASC, d.id ASC");
        $q->execute([$empId, $year, $month]);
        $rows = $q->fetchAll();
        if (! $rows) {
            return ['amount' => 0.0, 'items' => []];
        }
        $sumPrev = $d->prepare('SELECT COALESCE(SUM(deducted_amount),0) AS deducted FROM loan_deductions WHERE loan_id=? AND ((deduction_year < ?) OR (deduction_year = ? AND deduction_month < ?))');
        $upd = $d->prepare('UPDATE loan_deductions SET deducted_amount=?, balance_after=?, payroll_period=?, payroll_sheet_id=?, status=?, updated_at=? WHERE id=?');
        $loanUpd = $d->prepare('UPDATE loans SET status=?, updated_at=? WHERE id=?');
        $left = round($maxAvailable, 2);
        $total = 0.0;
        $items = [];
        foreach ($rows as $r) {
            $loanId = (string) $r['loan_id'];
            $sumPrev->execute([$loanId, $year, $year, $month]);
            $prevDeducted = round(f(($sumPrev->fetch() ?: [])['deducted'] ?? 0), 2);
            $loanAmount = round(f($r['requested_amount'] ?? 0), 2);
            $remainingBefore = round(max(0.0, $loanAmount - $prevDeducted), 2);
            $scheduled = round(min($remainingBefore, f($r['scheduled_amount'] ?? 0)), 2);
            $deducted = round(min($left, $scheduled), 2);
            $balanceAfter = round(max(0.0, $remainingBefore - $deducted), 2);
            $status = $deducted <= 0 ? 'Scheduled' : ($balanceAfter <= 0 ? 'Deducted' : ($deducted < $scheduled ? 'Partial' : 'Deducted'));
            $upd->execute([$deducted, $balanceAfter, $this->monthLabel($year, $month), $payrollSheetId, $status, now_iso(), (int) $r['id']]);
            $loanUpd->execute([$balanceAfter <= 0 ? 'Closed' : 'Active', now_iso(), $loanId]);
            if ($deducted > 0) {
                $total = round($total + $deducted, 2);
                $left = round(max(0.0, $left - $deducted), 2);
                $items[] = ['loanId' => $loanId, 'amount' => $deducted];
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
        $sql = 'SELECT * FROM loans WHERE emp_id=?';
        $params = [$empId];
        if ($asOfDate !== '') {
            $sql .= ' AND request_date<=?';
            $params[] = $asOfDate;
        }
        $sql .= ' ORDER BY created_at ASC';
        $q = $d->prepare($sql);
        $q->execute($params);
        $items = [];
        $total = 0.0;
        foreach ($q->fetchAll() as $r) {
            $row = $this->fetchOne($d, (string) $r['id']);
            if (! $row) {
                continue;
            }
            $remaining = round(f($row['balanceAmount'] ?? 0), 2);
            if ($remaining <= 0) {
                continue;
            }
            $items[] = [
                'loanId' => (string) ($row['id'] ?? ''),
                'loanType' => (string) ($row['loanType'] ?? ''),
                'remainingAmount' => $remaining,
            ];
            $total = round($total + $remaining, 2);
        }

        return ['amount' => $total, 'items' => $items];
    }

    private function employeeSnapshot(string $empId): array
    {
        $eid = up($empId);
        foreach (employees_all() as $emp) {
            if (up($emp['id'] ?? '') !== $eid) {
                continue;
            }
            $propertyBranch = s($emp['address'] ?? '');

            return [
                'empId' => $eid,
                'employeeName' => s($emp['name'] ?? $eid, $eid),
                'dept' => s($emp['dept'] ?? ''),
                'designation' => s($emp['desig'] ?? ''),
                'propertyBranch' => $propertyBranch,
            ];
        }
        nf('Employee not found');
    }

    private function monthLabel(int $year, int $month): string
    {
        if ($month < 1 || $month > 12 || $year < 2000) {
            return '';
        }

        return sprintf('%04d-%02d', $year, $month);
    }

    private function summary(PDO $d, string $loanId): array
    {
        $q = $d->prepare("SELECT COALESCE(SUM(deducted_amount),0) AS paid, MIN(CASE WHEN status<>'Deducted' AND balance_after>0 THEN (deduction_year*100 + deduction_month) ELSE NULL END) AS next_code FROM loan_deductions WHERE loan_id=?");
        $q->execute([$loanId]);
        $row = $q->fetch() ?: [];
        $paid = round(f($row['paid'] ?? 0), 2);
        $nextCode = (int) ($row['next_code'] ?? 0);
        $nextMonth = '';
        if ($nextCode > 0) {
            $nextYear = intdiv($nextCode, 100);
            $nextMon = $nextCode % 100;
            $nextMonth = $this->monthLabel($nextYear, $nextMon);
        }

        return ['paidAmount' => $paid, 'nextMonth' => $nextMonth];
    }

    private function payload(array $r): array
    {
        $base = [
            'id' => s($r['id'] ?? ''),
            'empId' => up($r['emp_id'] ?? $r['empId'] ?? ''),
            'employeeName' => s($r['employee_name'] ?? $r['employeeName'] ?? ''),
            'dept' => s($r['dept'] ?? ''),
            'designation' => s($r['designation'] ?? ''),
            'propertyBranch' => s($r['property_branch'] ?? $r['propertyBranch'] ?? ''),
            'loanType' => s($r['loan_type'] ?? $r['loanType'] ?? ''),
            'requestedAmount' => round(f($r['requested_amount'] ?? $r['requestedAmount'] ?? 0), 2),
            'reason' => s($r['reason'] ?? ''),
            'requestDate' => s($r['request_date'] ?? $r['requestDate'] ?? ''),
            'requiredDate' => s($r['required_date'] ?? $r['requiredDate'] ?? ''),
            'repaymentType' => s($r['repayment_type'] ?? $r['repaymentType'] ?? 'one_time', 'one_time'),
            'emiStartYear' => (int) ($r['emi_start_year'] ?? $r['emiStartYear'] ?? 0),
            'emiStartMonth' => (int) ($r['emi_start_month'] ?? $r['emiStartMonth'] ?? 0),
            'emiStartPeriod' => $this->monthLabel((int) ($r['emi_start_year'] ?? $r['emiStartYear'] ?? 0), (int) ($r['emi_start_month'] ?? $r['emiStartMonth'] ?? 0)),
            'emiAmount' => round(f($r['emi_amount'] ?? $r['emiAmount'] ?? 0), 2),
            'installmentCount' => (int) ($r['installment_count'] ?? $r['installmentCount'] ?? 1),
            'remarks' => s($r['remarks'] ?? ''),
            'status' => s($r['status'] ?? 'Active', 'Active'),
            'createdBy' => s($r['created_by'] ?? $r['createdBy'] ?? ''),
            'createdAt' => s($r['created_at'] ?? $r['createdAt'] ?? ''),
            'updatedAt' => s($r['updated_at'] ?? $r['updatedAt'] ?? ''),
        ];
        $paidAmount = round(f($r['paid_amount'] ?? $r['paidAmount'] ?? 0), 2);
        $balanceAmount = round(max(0.0, $base['requestedAmount'] - $paidAmount), 2);

        return $base + [
            'paidAmount' => $paidAmount,
            'balanceAmount' => $balanceAmount,
        ];
    }

    private function deductionHistoryRows(PDO $d, string $loanId): array
    {
        $q = $d->prepare('SELECT * FROM loan_deductions WHERE loan_id=? ORDER BY deduction_year ASC, deduction_month ASC, id ASC');
        $q->execute([$loanId]);
        $rows = [];
        foreach ($q->fetchAll() as $r) {
            $rows[] = [
                'id' => (int) ($r['id'] ?? 0),
                'loanId' => (string) ($r['loan_id'] ?? ''),
                'empId' => up($r['emp_id'] ?? ''),
                'year' => (int) ($r['deduction_year'] ?? 0),
                'month' => (int) ($r['deduction_month'] ?? 0),
                'period' => $this->monthLabel((int) ($r['deduction_year'] ?? 0), (int) ($r['deduction_month'] ?? 0)),
                'scheduledAmount' => round(f($r['scheduled_amount'] ?? 0), 2),
                'deductedAmount' => round(f($r['deducted_amount'] ?? 0), 2),
                'balanceAfter' => round(f($r['balance_after'] ?? 0), 2),
                'status' => s($r['status'] ?? ''),
                'payrollPeriod' => s($r['payroll_period'] ?? ''),
                'payrollSheetId' => s($r['payroll_sheet_id'] ?? ''),
                'createdAt' => s($r['created_at'] ?? ''),
                'updatedAt' => s($r['updated_at'] ?? ''),
            ];
        }

        return $rows;
    }

    private function scheduleRows(float $amount, string $repaymentType, float $emiAmount, int $installments, int $startYear, int $startMonth): array
    {
        $type = strtolower(trim($repaymentType));
        if ($type !== 'emi') {
            $type = 'one_time';
        }
        if ($startYear < 2000 || $startMonth < 1 || $startMonth > 12) {
            bad('Valid EMI start month is required');
        }
        if ($type === 'one_time') {
            return [['year' => $startYear, 'month' => $startMonth, 'scheduledAmount' => round($amount, 2)]];
        }
        $installments = max(1, $installments);
        $emiAmount = round(max(0.0, $emiAmount), 2);
        if ($emiAmount <= 0) {
            bad('EMI amount must be greater than 0 for EMI repayment');
        }
        $rows = [];
        $remaining = round($amount, 2);
        for ($i = 0; $i < $installments && $remaining > 0; $i++) {
            $p = advance_next_period($startYear, $startMonth, $i);
            $scheduled = round(min($emiAmount, $remaining), 2);
            $remaining = round(max(0.0, $remaining - $scheduled), 2);
            $rows[] = ['year' => (int) $p['year'], 'month' => (int) $p['month'], 'scheduledAmount' => $scheduled];
        }
        while ($remaining > 0) {
            $i = count($rows);
            $p = advance_next_period($startYear, $startMonth, $i);
            $scheduled = round(min($emiAmount, $remaining), 2);
            $remaining = round(max(0.0, $remaining - $scheduled), 2);
            $rows[] = ['year' => (int) $p['year'], 'month' => (int) $p['month'], 'scheduledAmount' => $scheduled];
        }

        return $rows;
    }
}
