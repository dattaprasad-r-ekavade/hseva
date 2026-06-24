<?php

namespace App\Services\Billing;

use App\Services\Access\AccessRepository;

class BillingRepository
{
    public function __construct(private AccessRepository $access) {}

    public function clientAccessTemplateGet(): array
    {
        $cid = req_client_id();
        if ($cid <= 0) {
            bad('clientId is required');
        }
        $q = central_db()->prepare('SELECT c.id, c.subscription_plan_id, p.access_type_code FROM clients c LEFT JOIN subscription_plans p ON p.id=c.subscription_plan_id WHERE c.id=? LIMIT 1');
        $q->execute([$cid]);
        $r = $q->fetch();
        if (! $r) {
            nf('Client not found');
        }
        $accessTypeCode = strtolower(s($r['access_type_code'] ?? '', ''));
        if ($accessTypeCode !== '') {
            return [
                'clientId' => $cid,
                'source' => 'subscription_plan',
                'accessTypeCode' => $accessTypeCode,
                'permissions' => $this->access->accessTypePermissions($accessTypeCode),
            ];
        }
        $acc = $this->access->accessGet($cid);

        return [
            'clientId' => $cid,
            'source' => 'client_access',
            'accessTypeCode' => (string) ($acc['accessType'] ?? 'custom'),
            'permissions' => $this->access->accessNormPermissions($acc['permissions'] ?? []),
        ];
    }

    public function billingAmountByAccessType(string $accessType): float
    {
        $t = strtolower(trim($accessType));
        if (str_contains($t, 'full')) {
            return 5000.0;
        }
        if (str_contains($t, 'payroll')) {
            return 3500.0;
        }
        if (str_contains($t, 'compliance')) {
            return 3000.0;
        }
        if (str_contains($t, 'read')) {
            return 2000.0;
        }

        return 3200.0;
    }

    public function clientBillingGet(): array
    {
        $cid = req_client_id();
        if ($cid <= 0) {
            j(['detail' => 'Client session required'], 401);
        }
        $q = central_db()->prepare('SELECT c.id, c.company_name, p.plan_name, p.status FROM clients c LEFT JOIN subscription_plans p ON p.id=c.subscription_plan_id WHERE c.id=? LIMIT 1');
        $q->execute([$cid]);
        $row = $q->fetch();
        if (! $row) {
            nf('Client not found');
        }

        $planName = s($row['plan_name'] ?? '', '-');
        $planStatus = s($row['status'] ?? '', '-');
        $subQ = central_db()->prepare('SELECT id, plan_name, start_date, end_date, renewal_date, status, amount, updated_at FROM subscriptions WHERE client_id=? ORDER BY start_date DESC, id DESC');
        $subQ->execute([$cid]);
        $subs = $subQ->fetchAll();
        $rows = [];
        $sumSubtotal = 0.0;
        $sumGst = 0.0;
        $sumTotal = 0.0;
        $sumPaid = 0.0;
        $sumPending = 0.0;

        foreach ($subs as $srow) {
            $id = (int) ($srow['id'] ?? 0);
            $issuedOn = s($srow['start_date'] ?? '', '');
            $dueDate = s($srow['renewal_date'] ?? '', '');
            $endDate = s($srow['end_date'] ?? '', '');
            $statusRaw = s($srow['status'] ?? '', 'Pending');
            $statusNorm = strtolower($statusRaw);
            $isPaid = in_array($statusNorm, ['paid', 'completed', 'settled', 'success'], true);
            $status = $isPaid ? 'Paid' : $statusRaw;
            $subtotal = round(f($srow['amount'] ?? 0), 2);
            $gst = round($subtotal * 0.18, 2);
            $total = round($subtotal + $gst, 2);
            $paidOn = $isPaid ? s($srow['updated_at'] ?? '', '') : null;
            $monthLabel = '-';
            if ($issuedOn !== '' && strtotime($issuedOn) !== false) {
                $monthLabel = gmdate('M Y', (int) strtotime($issuedOn));
            } elseif ($dueDate !== '' && strtotime($dueDate) !== false) {
                $monthLabel = gmdate('M Y', (int) strtotime($dueDate));
            }
            $invoiceNo = 'SUB-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
            $rows[] = [
                'id' => (string) $id,
                'invoiceNo' => $invoiceNo,
                'billingMonth' => $monthLabel,
                'planName' => s($srow['plan_name'] ?? '', $planName),
                'planStatus' => $status,
                'amount' => $subtotal,
                'gst' => $gst,
                'total' => $total,
                'status' => $status,
                'issuedOn' => $issuedOn,
                'dueDate' => $dueDate,
                'endDate' => $endDate,
                'paidOn' => $paidOn,
            ];
            $sumSubtotal += $subtotal;
            $sumGst += $gst;
            $sumTotal += $total;
            if ($isPaid) {
                $sumPaid += $total;
            } else {
                $sumPending += $total;
            }
        }

        return [
            'clientId' => (int) $cid,
            'clientName' => (string) ($row['company_name'] ?? ''),
            'currentPlan' => ['planName' => $planName, 'status' => $planStatus],
            'summary' => [
                'subtotal' => round($sumSubtotal, 2),
                'gst' => round($sumGst, 2),
                'total' => round($sumTotal, 2),
                'paid' => round($sumPaid, 2),
                'pending' => round($sumPending, 2),
            ],
            'rows' => $rows,
        ];
    }

    public function clientInvoicesGet(): array
    {
        $bill = $this->clientBillingGet();
        $rows = [];
        foreach (($bill['rows'] ?? []) as $r) {
            $rows[] = $r + [
                'invoiceTitle' => (string) ($r['invoiceNo'] ?? ''),
                'downloadUrl' => '',
            ];
        }

        return [
            'clientId' => (int) ($bill['clientId'] ?? 0),
            'clientName' => (string) ($bill['clientName'] ?? ''),
            'currentPlan' => $bill['currentPlan'] ?? null,
            'summary' => $bill['summary'] ?? [],
            'rows' => $rows,
        ];
    }
}
