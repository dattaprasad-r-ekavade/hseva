<?php

namespace App\Services\Shift;

use PDO;

class ShiftAccess
{
    public function requireAccess(): void
    {
        $ctx = auth_ctx(false);
        if (! $ctx) {
            j(['detail' => 'Unauthorized'], 401);
        }
        $role = strtolower((string) ($ctx['role'] ?? ''));
        if (in_array($role, ['super_admin', 'agency_admin', 'client_admin', 'employee'], true)) {
            return;
        }
        if ($role === 'client') {
            $perm = $ctx['permissions'] ?? [];
            if (is_array($perm) && array_key_exists('shiftRoster', $perm) && $perm['shiftRoster'] === false) {
                j(['detail' => 'Forbidden'], 403);
            }

            return;
        }
    }

    public function actorName(): string
    {
        $ctx = auth_ctx(false);

        return s($ctx['username'] ?? $ctx['name'] ?? $ctx['sub'] ?? 'system', 'system');
    }

    public function companyIdsScope(bool $allowAll, ?int $companyId = null): array
    {
        $ctx = auth_ctx(true);
        $role = strtolower((string) ($ctx['role'] ?? ''));
        $qCompany = $companyId ?? (isset($_GET['companyId']) ? (int) $_GET['companyId'] : 0);
        if ($role === 'super_admin') {
            if ($qCompany > 0) {
                return [$qCompany];
            }
            if (! $allowAll) {
                $hid = req_client_id();

                return $hid > 0 ? [$hid] : [];
            }
            $rows = central_db()->query('SELECT id FROM clients ORDER BY id ASC')->fetchAll();
            $ids = [];
            foreach ($rows as $r) {
                $id = (int) ($r['id'] ?? 0);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }

            return $ids;
        }
        $cid = (int) ($ctx['clientId'] ?? 0);
        if ($cid <= 0) {
            $cid = req_client_id();
        }

        return $cid > 0 ? [$cid] : [];
    }

    public function writeCompanyId(array $payload): int
    {
        $ctx = auth_ctx(true);
        $role = strtolower((string) ($ctx['role'] ?? ''));
        $req = (int) ($payload['companyId'] ?? 0);
        if ($role === 'super_admin') {
            $fromHeader = req_client_id();
            $fromQuery = isset($_GET['companyId']) ? (int) $_GET['companyId'] : 0;
            $id = $req > 0 ? $req : ($fromHeader > 0 ? $fromHeader : ($fromQuery > 0 ? $fromQuery : 0));
            if ($id <= 0) {
                bad('companyId is required for super admin write');
            }

            return $id;
        }
        $cid = (int) ($ctx['clientId'] ?? 0);
        if ($cid <= 0) {
            $cid = req_client_id();
        }
        if ($cid <= 0) {
            bad('Client scope is required');
        }

        return $cid;
    }

    public function dbForCompany(int $companyId): PDO
    {
        return db_open(db_path_for_client($companyId));
    }

    public function companyName(int $companyId): string
    {
        $q = central_db()->prepare('SELECT company_name FROM clients WHERE id=?');
        $q->execute([$companyId]);
        $row = $q->fetch();

        return s($row['company_name'] ?? '', 'Company '.$companyId);
    }
}
