<?php

namespace App\Services\Subscriptions;

use App\Services\Access\AccessRepository;
use App\Services\Clients\ClientRepository;

class SubscriptionRepository
{
    public function __construct(
        private AccessRepository $access,
        private ClientRepository $clients,
    ) {}

    public function subscriptionNorm(array $r): array
    {
        $clientId = (int) ($r['clientId'] ?? 0);
        $planName = s($r['planName'] ?? '');
        $startDate = s($r['startDate'] ?? '');
        $endDate = s($r['endDate'] ?? '');
        $renewalDate = s($r['renewalDate'] ?? '');
        $status = s($r['status'] ?? 'Active', 'Active');
        $amount = f($r['amount'] ?? 0);
        $notes = s($r['notes'] ?? '');
        if ($clientId <= 0) {
            bad('clientId is required');
        }
        if ($planName === '') {
            bad('planName is required');
        }
        if ($startDate === '' || $endDate === '' || $renewalDate === '') {
            bad('startDate, endDate and renewalDate are required');
        }
        if (! $this->clients->clientExists($clientId)) {
            bad('Invalid clientId');
        }

        return ['id' => isset($r['id']) ? (int) $r['id'] : null, 'clientId' => $clientId, 'planName' => $planName, 'startDate' => $startDate, 'endDate' => $endDate, 'renewalDate' => $renewalDate, 'status' => $status, 'amount' => $amount, 'notes' => $notes];
    }

    public function subscriptionsAll(): array
    {
        $rows = central_db()->query('SELECT s.*, c.company_name AS client_name, c.user_id AS user_id FROM subscriptions s LEFT JOIN clients c ON c.id=s.client_id ORDER BY s.updated_at DESC, s.id DESC')->fetchAll();

        return array_map(fn ($r) => [
            'id' => (int) $r['id'],
            'clientId' => (int) $r['client_id'],
            'clientName' => (string) ($r['client_name'] ?? ''),
            'userId' => (string) ($r['user_id'] ?? ''),
            'planName' => (string) $r['plan_name'],
            'startDate' => (string) $r['start_date'],
            'endDate' => (string) $r['end_date'],
            'renewalDate' => (string) $r['renewal_date'],
            'status' => (string) $r['status'],
            'amount' => (float) $r['amount'],
            'notes' => (string) $r['notes'],
            '__updatedAt' => (string) $r['updated_at'],
        ], $rows);
    }

    public function subscriptionUpsert(array $raw, ?bool $mustExist = null): array
    {
        $n = $this->subscriptionNorm($raw);
        $id = $n['id'];
        $d = central_db();
        $isNew = ! ($id && $id > 0);
        if ($mustExist === true && (! $id || $id <= 0)) {
            bad('subscription id is required');
        }
        if ($id && $id > 0) {
            $q = $d->prepare('SELECT id FROM subscriptions WHERE id=?');
            $q->execute([$id]);
            if ($mustExist === true && ! $q->fetch()) {
                nf('Subscription not found');
            }
        }
        $ts = now_iso();
        if ($id && $id > 0) {
            $st = $d->prepare('UPDATE subscriptions SET client_id=?, plan_name=?, start_date=?, end_date=?, renewal_date=?, status=?, amount=?, notes=?, updated_at=? WHERE id=?');
            $st->execute([$n['clientId'], $n['planName'], $n['startDate'], $n['endDate'], $n['renewalDate'], $n['status'], $n['amount'], $n['notes'], $ts, $id]);
        } else {
            $st = $d->prepare('INSERT INTO subscriptions (client_id, plan_name, start_date, end_date, renewal_date, status, amount, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $st->execute([$n['clientId'], $n['planName'], $n['startDate'], $n['endDate'], $n['renewalDate'], $n['status'], $n['amount'], $n['notes'], $ts, $ts]);
            $id = (int) $d->lastInsertId();
        }
        $n['id'] = $id;
        $q = $d->prepare('SELECT company_name, user_id FROM clients WHERE id=?');
        $q->execute([$n['clientId']]);
        $c = $q->fetch() ?: ['company_name' => '', 'user_id' => ''];
        $row = $n + ['clientName' => (string) $c['company_name'], 'userId' => (string) $c['user_id'], '__updatedAt' => $ts];
        mail_subscription_event((string) $id, $row, $isNew);

        return $row;
    }

    public function subscriptionDelete(int $id): void
    {
        $st = central_db()->prepare('DELETE FROM subscriptions WHERE id=?');
        $st->execute([$id]);
        if ($st->rowCount() === 0) {
            nf('Subscription not found');
        }
    }

    public function planNorm(array $r): array
    {
        $name = s($r['planName'] ?? '');
        if ($name === '') {
            bad('planName is required');
        }
        $duration = (int) ($r['durationMonths'] ?? 12);
        if ($duration <= 0) {
            bad('durationMonths must be > 0');
        }
        $amount = f($r['amount'] ?? 0);
        $status = s($r['status'] ?? 'Active', 'Active');
        $features = s($r['features'] ?? '');
        $accessTypeCode = strtolower(s($r['accessTypeCode'] ?? 'full_access', 'full_access'));
        if ($this->access->accessTypeGet($accessTypeCode) === null) {
            bad('Invalid accessTypeCode');
        }

        return ['id' => isset($r['id']) ? (int) $r['id'] : null, 'planName' => $name, 'durationMonths' => $duration, 'amount' => $amount, 'status' => $status, 'features' => $features, 'accessTypeCode' => $accessTypeCode];
    }

    public function plansAll(): array
    {
        $rows = central_db()->query('SELECT p.*, a.name AS access_type_name FROM subscription_plans p LEFT JOIN access_types a ON a.code=p.access_type_code ORDER BY p.updated_at DESC, p.id DESC')->fetchAll();

        return array_map(fn ($r) => [
            'id' => (int) $r['id'],
            'planName' => (string) $r['plan_name'],
            'durationMonths' => (int) $r['duration_months'],
            'amount' => (float) $r['amount'],
            'status' => (string) $r['status'],
            'features' => (string) $r['features'],
            'accessTypeCode' => (string) ($r['access_type_code'] ?? 'full_access'),
            'accessTypeName' => (string) ($r['access_type_name'] ?? ($r['access_type_code'] ?? '')),
            '__updatedAt' => (string) $r['updated_at'],
        ], $rows);
    }

    public function planUpsert(array $raw, ?bool $mustExist = null): array
    {
        $n = $this->planNorm($raw);
        $id = $n['id'];
        $d = central_db();
        if ($mustExist === true && (! $id || $id <= 0)) {
            bad('plan id is required');
        }
        if ($id && $id > 0) {
            $q = $d->prepare('SELECT id FROM subscription_plans WHERE id=?');
            $q->execute([$id]);
            if ($mustExist === true && ! $q->fetch()) {
                nf('Plan not found');
            }
        }
        $dup = $d->prepare('SELECT id FROM subscription_plans WHERE lower(plan_name)=lower(?) AND id<>?');
        $dup->execute([$n['planName'], (int) ($id ?? 0)]);
        if ($dup->fetch()) {
            j(['detail' => 'Plan name already exists'], 409);
        }
        $ts = now_iso();
        if ($id && $id > 0) {
            $st = $d->prepare('UPDATE subscription_plans SET plan_name=?, duration_months=?, amount=?, status=?, features=?, access_type_code=?, updated_at=? WHERE id=?');
            $st->execute([$n['planName'], $n['durationMonths'], $n['amount'], $n['status'], $n['features'], $n['accessTypeCode'], $ts, $id]);
        } else {
            $st = $d->prepare('INSERT INTO subscription_plans (plan_name, duration_months, amount, status, features, access_type_code, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)');
            $st->execute([$n['planName'], $n['durationMonths'], $n['amount'], $n['status'], $n['features'], $n['accessTypeCode'], $ts, $ts]);
            $id = (int) $d->lastInsertId();
        }
        $n['id'] = $id;
        $at = $this->access->accessTypeGet($n['accessTypeCode']);
        $n['accessTypeName'] = $at['name'] ?? $n['accessTypeCode'];

        return $n + ['__updatedAt' => $ts];
    }

    public function planDelete(int $id): void
    {
        $st = central_db()->prepare('DELETE FROM subscription_plans WHERE id=?');
        $st->execute([$id]);
        if ($st->rowCount() === 0) {
            nf('Plan not found');
        }
    }

    public function subscriptionInfoGet(): array
    {
        $cid = req_client_id();
        $plans = $this->plansAll();
        if ($cid <= 0) {
            return ['clientId' => 0, 'currentPlan' => null, 'plans' => $plans];
        }
        $q = central_db()->prepare('SELECT c.id, c.subscription_plan_id, p.plan_name, p.duration_months, p.amount, p.status, p.features, p.access_type_code FROM clients c LEFT JOIN subscription_plans p ON p.id=c.subscription_plan_id WHERE c.id=? LIMIT 1');
        $q->execute([$cid]);
        $r = $q->fetch();
        if (! $r) {
            nf('Client not found');
        }
        $current = null;
        $curId = (int) ($r['subscription_plan_id'] ?? 0);
        $startDate = '';
        $endDate = '';
        $renewalDate = '';
        $subStatus = '';
        $sq = central_db()->prepare('SELECT start_date, end_date, renewal_date, status FROM subscriptions WHERE client_id=? ORDER BY end_date DESC, id DESC LIMIT 1');
        $sq->execute([$cid]);
        $sr = $sq->fetch();
        if ($sr) {
            $startDate = (string) ($sr['start_date'] ?? '');
            $endDate = (string) ($sr['end_date'] ?? '');
            $renewalDate = (string) ($sr['renewal_date'] ?? '');
            $subStatus = (string) ($sr['status'] ?? '');
        }
        if ($curId > 0) {
            $at = $this->access->accessTypeGet((string) ($r['access_type_code'] ?? ''));
            $current = [
                'id' => $curId,
                'planName' => (string) ($r['plan_name'] ?? ''),
                'durationMonths' => (int) ($r['duration_months'] ?? 0),
                'amount' => (float) ($r['amount'] ?? 0),
                'status' => $subStatus !== '' ? $subStatus : (string) ($r['status'] ?? ''),
                'features' => (string) ($r['features'] ?? ''),
                'accessTypeCode' => (string) ($r['access_type_code'] ?? ''),
                'accessTypeName' => (string) ($at['name'] ?? ($r['access_type_code'] ?? '')),
                'startDate' => $startDate,
                'endDate' => $endDate,
                'renewalDate' => $renewalDate,
            ];
        }

        return ['clientId' => $cid, 'currentPlan' => $current, 'plans' => $plans];
    }
}
