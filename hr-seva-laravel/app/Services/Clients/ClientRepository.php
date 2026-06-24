<?php

namespace App\Services\Clients;

use App\Services\Access\AccessRepository;

class ClientRepository
{
    public function __construct(private AccessRepository $access) {}

    public function clientsAll(): array
    {
        $rows = central_db()->query("SELECT c.*, COALESCE(a.access_type, 'custom') AS access_type, COALESCE(p.plan_name, '') AS subscription_type_name FROM clients c LEFT JOIN client_access a ON a.client_id=c.id LEFT JOIN subscription_plans p ON p.id=c.subscription_plan_id ORDER BY c.id DESC")->fetchAll();

        return array_map(fn ($r) => [
            'id' => (int) $r['id'],
            'companyName' => (string) $r['company_name'],
            'companyAddress' => (string) $r['company_address'],
            'companyRegNo' => (string) $r['company_reg_no'],
            'companyPAN' => (string) $r['company_pan'],
            'companyTAN' => (string) $r['company_tan'],
            'companyGSTIN' => (string) $r['company_gstin'],
            'companyContactNo' => (string) $r['company_contact_no'],
            'companyEmail' => (string) ($r['company_email'] ?? ''),
            'userId' => (string) ($r['user_id'] ?? ''),
            'accessType' => (string) ($r['access_type'] ?? 'custom'),
            'subscriptionPlanId' => (int) ($r['subscription_plan_id'] ?? 0),
            'subscriptionTypeName' => (string) ($r['subscription_type_name'] ?? ''),
            '__updatedAt' => (string) $r['updated_at'],
        ], $rows);
    }

    public function clientUpsert(array $raw, ?bool $mustExist = null): array
    {
        $n = $this->normClient($raw, $mustExist === true);
        $id = $n['id'];
        $d = central_db();
        $isNew = ! ($id && $id > 0);
        if ($mustExist === true && (! $id || $id <= 0)) {
            bad('Client id is required');
        }
        if ($id && $id > 0) {
            $q = $d->prepare('SELECT id FROM clients WHERE id=?');
            $q->execute([$id]);
            $exists = (bool) $q->fetch();
            if ($mustExist === true && ! $exists) {
                nf('Client not found');
            }
        }
        $du = $d->prepare('SELECT id FROM clients WHERE lower(user_id)=? AND id<>?');
        $du->execute([$n['userId'], (int) ($id ?? 0)]);
        if ($du->fetch()) {
            j(['detail' => 'User ID already exists'], 409);
        }
        $ts = now_iso();
        $pwdHash = $n['userPassword'] !== '' ? password_hash($n['userPassword'], PASSWORD_DEFAULT) : '';
        $effectiveAccessType = $n['accessType'];
        if ($n['subscriptionPlanId'] > 0) {
            $qp = $d->prepare('SELECT access_type_code FROM subscription_plans WHERE id=?');
            $qp->execute([$n['subscriptionPlanId']]);
            $pr = $qp->fetch();
            if (! $pr) {
                bad('Invalid subscriptionPlanId');
            }
            $effectiveAccessType = strtolower(s($pr['access_type_code'] ?? 'full_access', 'full_access'));
        }
        $syncTenant = function (int $clientId) use ($n): void {
            if ($clientId <= 0) {
                return;
            }
            $td = db_open(db_path_for_client($clientId));
            init_schema($td);
            $existingProfile = null;
            $pst = $td->prepare("SELECT value FROM tenant_settings WHERE key='company_profile'");
            $pst->execute();
            $pr = $pst->fetch();
            if ($pr && isset($pr['value'])) {
                $decoded = json_decode((string) $pr['value'], true);
                if (is_array($decoded)) {
                    $existingProfile = $decoded;
                }
            }
            $existingControl = null;
            $cst = $td->prepare("SELECT value FROM tenant_settings WHERE key='control_settings'");
            $cst->execute();
            $cr = $cst->fetch();
            if ($cr && isset($cr['value'])) {
                $decoded = json_decode((string) $cr['value'], true);
                if (is_array($decoded)) {
                    $existingControl = $decoded;
                }
            }
            $profile = is_array($existingProfile) ? $existingProfile : [];
            $control = is_array($existingControl) ? $existingControl : [];
            $profile['companyName'] = $n['companyName'];
            $profile['companyAddress'] = $n['companyAddress'];
            $profile['regNo'] = $n['companyRegNo'];
            $profile['pan'] = $n['companyPAN'];
            $profile['tan'] = $n['companyTAN'];
            $profile['gstin'] = $n['companyGSTIN'];
            $profile['contactNo'] = $n['companyContactNo'];
            $profile['email'] = $n['companyEmail'];
            $control['companyName'] = $n['companyName'];
            $control['companyAddress'] = $n['companyAddress'];
            $control['companyRegNo'] = $n['companyRegNo'];
            $control['companyPAN'] = $n['companyPAN'];
            $control['companyTAN'] = $n['companyTAN'];
            $control['companyGSTIN'] = $n['companyGSTIN'];
            $control['companyContact'] = $n['companyContactNo'];
            $writeSetting = function (string $key, array $value) use ($td): void {
                $json = json_encode($value, JSON_UNESCAPED_UNICODE);
                $now = now_iso();
                $st = $td->prepare('INSERT INTO tenant_settings (key,value,updated_at) VALUES (?,?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=excluded.updated_at');
                $st->execute([$key, $json, $now]);
            };
            $writeSetting('company_profile', $profile);
            $writeSetting('control_settings', $control);
        };
        if ($id && $id > 0) {
            if ($pwdHash !== '') {
                $st = $d->prepare('UPDATE clients SET company_name=?,company_address=?,company_reg_no=?,company_pan=?,company_tan=?,company_gstin=?,company_contact_no=?,company_email=?,user_id=?,user_password=\'\',user_password_hash=?,subscription_plan_id=?,updated_at=? WHERE id=?');
                $st->execute([$n['companyName'], $n['companyAddress'], $n['companyRegNo'], $n['companyPAN'], $n['companyTAN'], $n['companyGSTIN'], $n['companyContactNo'], $n['companyEmail'], $n['userId'], $pwdHash, $n['subscriptionPlanId'], $ts, $id]);
            } else {
                $st = $d->prepare('UPDATE clients SET company_name=?,company_address=?,company_reg_no=?,company_pan=?,company_tan=?,company_gstin=?,company_contact_no=?,company_email=?,user_id=?,subscription_plan_id=?,updated_at=? WHERE id=?');
                $st->execute([$n['companyName'], $n['companyAddress'], $n['companyRegNo'], $n['companyPAN'], $n['companyTAN'], $n['companyGSTIN'], $n['companyContactNo'], $n['companyEmail'], $n['userId'], $n['subscriptionPlanId'], $ts, $id]);
            }
            if ($effectiveAccessType !== '') {
                $ap = $this->access->accessTypePermissions($effectiveAccessType);
                $this->access->accessPut($id, ['accessType' => $effectiveAccessType, 'permissions' => $ap]);
            }
            $syncTenant($id);
        } else {
            $st = $d->prepare('INSERT INTO clients (company_name,company_address,company_reg_no,company_pan,company_tan,company_gstin,company_contact_no,company_email,user_id,user_password,user_password_hash,subscription_plan_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([$n['companyName'], $n['companyAddress'], $n['companyRegNo'], $n['companyPAN'], $n['companyTAN'], $n['companyGSTIN'], $n['companyContactNo'], $n['companyEmail'], $n['userId'], '', $pwdHash, $n['subscriptionPlanId'], $ts, $ts]);
            $id = (int) $d->lastInsertId();
            if ($effectiveAccessType !== '') {
                $ap = $this->access->accessTypePermissions($effectiveAccessType);
                $this->access->accessPut($id, ['accessType' => $effectiveAccessType, 'permissions' => $ap]);
            }
            $syncTenant($id);
        }
        $n['id'] = $id;
        $plainPassword = (string) ($n['userPassword'] ?? '');
        unset($n['userPassword']);
        $row = $n + ['__updatedAt' => $ts];
        mail_client_onboarding((string) $id, $row, $plainPassword, $isNew);

        return $row;
    }

    public function clientDelete(int $id): void
    {
        $d = central_db();
        $st = $d->prepare('DELETE FROM clients WHERE id=?');
        $st->execute([$id]);
        $d->prepare('DELETE FROM client_access WHERE client_id=?')->execute([$id]);
        $d->prepare('DELETE FROM staff_roles WHERE client_id=?')->execute([$id]);
        $d->prepare('DELETE FROM staff_users WHERE client_id=?')->execute([$id]);
        if ($st->rowCount() === 0) {
            nf('Client not found');
        }
    }

    public function clear(): void
    {
        central_db()->exec('DELETE FROM clients');
        central_db()->exec('DELETE FROM client_access');
    }

    public function clientExists(int $id): bool
    {
        $q = central_db()->prepare('SELECT id FROM clients WHERE id=?');
        $q->execute([$id]);

        return (bool) $q->fetch();
    }

    private function normClient(array $r, bool $isUpdate = false): array
    {
        $name = s($r['companyName'] ?? '');
        if ($name === '') {
            bad('Company Name is required');
        }
        $userId = strtolower(s($r['userId'] ?? ''));
        $userPassword = s($r['userPassword'] ?? '');
        if ($userId === '') {
            bad('User ID is required');
        }
        if (! $isUpdate && $userPassword === '') {
            bad('Password is required');
        }

        return [
            'id' => isset($r['id']) ? (int) $r['id'] : null,
            'companyName' => $name,
            'companyAddress' => s($r['companyAddress'] ?? ''),
            'companyRegNo' => s($r['companyRegNo'] ?? ''),
            'companyPAN' => up($r['companyPAN'] ?? ''),
            'companyTAN' => up($r['companyTAN'] ?? ''),
            'companyGSTIN' => up($r['companyGSTIN'] ?? ''),
            'companyContactNo' => s($r['companyContactNo'] ?? ''),
            'companyEmail' => strtolower(s($r['companyEmail'] ?? '')),
            'userId' => $userId,
            'userPassword' => $userPassword,
            'accessType' => strtolower(s($r['accessType'] ?? '', '')),
            'subscriptionPlanId' => (int) ($r['subscriptionPlanId'] ?? 0),
        ];
    }
}
