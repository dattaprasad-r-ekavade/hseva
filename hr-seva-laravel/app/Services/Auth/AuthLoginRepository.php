<?php

namespace App\Services\Auth;

class AuthLoginRepository
{
    public function __construct(
        private JwtService $jwt,
        private AuthUsersRepository $users,
    ) {}

    public function login(string $username, string $password): array
    {
        $u = strtolower(trim($username));
        $p = trim($password);
        if ($u === '' || $p === '') {
            bad('username and password are required');
        }
        login_rate_limit_check($u);
        $users = $this->users->all();
        $usersChanged = false;
        foreach ($users as $idx => $x) {
            if (strtolower((string) ($x['username'] ?? '')) === $u && auth_user_verify($x, $p)) {
                if ((string) ($x['passwordHash'] ?? '') === '') {
                    $users[$idx]['passwordHash'] = password_hash($p, PASSWORD_DEFAULT);
                    $users[$idx]['password'] = '';
                    $usersChanged = true;
                }
                if ($usersChanged) {
                    $this->users->save($users);
                }
                login_rate_limit_success($u);
                $now = time();
                $role = (string) ($x['role'] ?? 'super_admin');
                $tokenClientId = (int) ($x['clientId'] ?? 0);
                $tokenEmpId = up($x['empId'] ?? '');
                $tok = $this->jwt->sign([
                    'sub' => $u,
                    'name' => (string) ($x['name'] ?? $u),
                    'role' => $role,
                    'clientId' => $tokenClientId,
                    'empId' => $tokenEmpId,
                    'iat' => $now,
                    'exp' => $now + $this->jwt->ttl(),
                ]);

                return ['ok' => true, 'token' => $tok, 'user' => ['username' => $u, 'name' => $x['name'] ?? $u, 'role' => $role, 'clientId' => $tokenClientId, 'empId' => $tokenEmpId]];
            }
        }
        $q = central_db()->prepare('SELECT id, company_name, subscription_plan_id, user_password, user_password_hash FROM clients WHERE lower(user_id)=? LIMIT 1');
        $q->execute([$u]);
        $row = $q->fetch();
        if ($row) {
            $sub = client_subscription_access_state((int) $row['id']);
            if (empty($sub['active'])) {
                j(['detail' => 'Subscription expired. Access denied.', 'reason' => $sub['reason'] ?? '', 'endDate' => $sub['endDate'] ?? null], 403);
            }
            $ok = false;
            $hash = (string) ($row['user_password_hash'] ?? '');
            if ($hash !== '' && password_verify($p, $hash)) {
                $ok = true;
            } else {
                $plain = (string) ($row['user_password'] ?? '');
                if ($plain !== '' && hash_equals($plain, $p)) {
                    $ok = true;
                    $newHash = password_hash($p, PASSWORD_DEFAULT);
                    $m = central_db()->prepare("UPDATE clients SET user_password='', user_password_hash=?, updated_at=? WHERE id=?");
                    $m->execute([$newHash, now_iso(), (int) $row['id']]);
                }
            }
            if (! $ok) {
                login_rate_limit_fail($u);
                j(['detail' => 'Invalid credentials'], 401);
            }
            login_rate_limit_success($u);
            $cid = (int) $row['id'];
            $planId = (int) ($row['subscription_plan_id'] ?? 0);
            if ($planId > 0) {
                $sp = central_db()->prepare('SELECT access_type_code FROM subscription_plans WHERE id=? LIMIT 1');
                $sp->execute([$planId]);
                $pr = $sp->fetch();
                if ($pr) {
                    $planAccessType = strtolower(s($pr['access_type_code'] ?? ''));
                    if ($planAccessType !== '') {
                        access_put($cid, ['accessType' => $planAccessType, 'permissions' => access_type_permissions($planAccessType)]);
                    }
                }
            }
            $acc = access_get($cid);
            $now = time();
            $tok = $this->jwt->sign([
                'sub' => $u,
                'name' => (string) $row['company_name'],
                'role' => 'client',
                'clientId' => $cid,
                'iat' => $now,
                'exp' => $now + $this->jwt->ttl(),
            ]);

            return ['ok' => true, 'token' => $tok, 'user' => ['username' => $u, 'name' => (string) $row['company_name'], 'role' => 'client', 'clientId' => $cid, 'permissions' => $acc['permissions']]];
        }
        $staff = staff_user_get_by_username($u);
        if ($staff) {
            if (strtolower((string) ($staff['status'] ?? 'active')) !== 'active') {
                login_rate_limit_fail($u);
                j(['detail' => 'Account is inactive'], 403);
            }
            $hash = (string) ($staff['passwordHash'] ?? '');
            if ($hash === '' || ! password_verify($p, $hash)) {
                login_rate_limit_fail($u);
                j(['detail' => 'Invalid credentials'], 401);
            }
            $cid = (int) ($staff['clientId'] ?? 0);
            if ($cid <= 0 || ! client_exists($cid)) {
                login_rate_limit_fail($u);
                j(['detail' => 'Invalid staff account'], 403);
            }
            $sub = client_subscription_access_state($cid);
            if (empty($sub['active'])) {
                j(['detail' => 'Subscription expired. Access denied.', 'reason' => $sub['reason'] ?? '', 'endDate' => $sub['endDate'] ?? null], 403);
            }
            login_rate_limit_success($u);
            $companyAccess = access_get($cid);
            $rolePerm = staff_role_permissions($cid, (string) ($staff['roleCode'] ?? ''));
            $effectivePerm = perm_intersect($companyAccess['permissions'] ?? access_default_permissions(), $rolePerm);
            $now = time();
            $empId = up($staff['empId'] ?? '');
            $tok = $this->jwt->sign([
                'sub' => $u,
                'name' => (string) ($staff['username'] ?? $u),
                'role' => 'employee',
                'clientId' => $cid,
                'empId' => $empId,
                'iat' => $now,
                'exp' => $now + $this->jwt->ttl(),
            ]);

            return ['ok' => true, 'token' => $tok, 'user' => ['username' => $u, 'name' => (string) ($staff['username'] ?? $u), 'role' => 'employee', 'clientId' => $cid, 'empId' => $empId, 'permissions' => $effectivePerm]];
        }
        login_rate_limit_fail($u);
        j(['detail' => 'Invalid credentials'], 401);
    }

    public function forgot(array $raw): array
    {
        $email = mail_valid_email_or_blank((string) ($raw['email'] ?? ''));
        if ($email === '') {
            bad('email is required');
        }

        $subject = 'HR Seva password assistance request';
        $matched = false;
        $clientCtx = null;
        $q = central_db()->prepare('SELECT id, company_name, user_id FROM clients WHERE lower(user_id)=lower(?) LIMIT 1');
        $q->execute([$email]);
        $client = $q->fetch();

        if ($client) {
            $matched = true;
            $clientCtx = client_contact_context((int) $client['id']);
            $facts = ['Company' => (string) ($client['company_name'] ?? ''), 'Username' => (string) ($client['user_id'] ?? '')];
            mail_send_logged('forgot_password_customer', 'client_'.(int) $client['id'], (int) $client['id'], [$email], $subject, mail_shell_html('Password Assistance Request', 'We received a password assistance request for your HR Seva client account.', $facts, 'For security, our team will help you with the next step.'));
        } else {
            $staff = staff_user_get_by_username($email);
            if ($staff) {
                $matched = true;
                $clientCtx = client_contact_context((int) ($staff['clientId'] ?? 0));
                $facts = ['Username' => (string) ($staff['username'] ?? ''), 'Employee ID' => (string) ($staff['empId'] ?? ''), 'Company' => $clientCtx['companyName']];
                mail_send_logged('forgot_password_customer', 'staff_'.(int) ($staff['id'] ?? 0), (int) ($staff['clientId'] ?? 0), [$email], $subject, mail_shell_html('Password Assistance Request', 'We received a password assistance request for your HR Seva staff account.', $facts, 'For security, our team will help you with the next step.'));
            }
        }

        $facts = ['Requested Email' => $email, 'Matched Account' => $matched ? 'Yes' : 'No'];
        if ($clientCtx) {
            $facts['Company'] = (string) ($clientCtx['companyName'] ?? '');
        }
        mail_send_admins('forgot_password_admin', $email, (int) ($clientCtx['clientId'] ?? 0), 'Password assistance request | HR Seva', 'Password assistance requested', 'A password assistance request was submitted.', $facts);

        return ['ok' => true, 'message' => 'If this email is registered, reset instructions will be sent.'];
    }
}
