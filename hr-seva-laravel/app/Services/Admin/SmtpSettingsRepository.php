<?php

namespace App\Services\Admin;

class SmtpSettingsRepository
{
    public function defaults(): array
    {
        $cfg = hr_mail_config();

        return [
            'enabled' => (bool) ($cfg['enabled'] ?? false),
            'host' => (string) ($cfg['host'] ?? ''),
            'port' => (int) ($cfg['port'] ?? 465),
            'encryption' => (string) ($cfg['encryption'] ?? 'ssl'),
            'username' => (string) ($cfg['username'] ?? ''),
            'password' => '',
            'fromEmail' => (string) ($cfg['from_email'] ?? ''),
            'fromName' => (string) ($cfg['from_name'] ?? 'HR Seva'),
            'replyTo' => (string) ($cfg['reply_to'] ?? ''),
            'adminEmails' => (string) ($cfg['admin_emails'] ?? ''),
            'hasPassword' => s((string) ($cfg['password'] ?? '')) !== '',
            '__source' => 'effective',
            '__lastSaved' => null,
        ];
    }

    public function get(): array
    {
        require_super_admin();
        $base = $this->defaults();
        $st = central_db()->prepare('SELECT value, updated_at FROM app_kv WHERE key=? LIMIT 1');
        $st->execute(['smtp_settings']);
        $row = $st->fetch();
        if (! $row) {
            return $base;
        }
        $val = json_decode((string) ($row['value'] ?? ''), true);
        $stored = is_array($val) ? $val : [];

        return [
            'enabled' => b($stored['HR_SMTP_ENABLED'] ?? ($base['enabled'] ? 'true' : 'false')),
            'host' => s($stored['HR_SMTP_HOST'] ?? $base['host']),
            'port' => (int) ($stored['HR_SMTP_PORT'] ?? $base['port']),
            'encryption' => s($stored['HR_SMTP_ENCRYPTION'] ?? $base['encryption'], 'ssl'),
            'username' => s($stored['HR_SMTP_USERNAME'] ?? $base['username']),
            'password' => '',
            'fromEmail' => s($stored['HR_SMTP_FROM_EMAIL'] ?? $base['fromEmail']),
            'fromName' => s($stored['HR_SMTP_FROM_NAME'] ?? $base['fromName'], 'HR Seva'),
            'replyTo' => s($stored['HR_SMTP_REPLY_TO'] ?? $base['replyTo']),
            'adminEmails' => s($stored['HR_SMTP_ADMIN_EMAILS'] ?? $base['adminEmails']),
            'hasPassword' => s($stored['HR_SMTP_PASSWORD'] ?? '') !== '' || (bool) $base['hasPassword'],
            '__source' => 'db',
            '__lastSaved' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    public function put(array $raw): array
    {
        require_super_admin();
        $password = s($raw['password'] ?? '');
        $username = s($raw['username'] ?? '');
        $fromEmail = s($raw['fromEmail'] ?? '');
        if ($username !== '' && $fromEmail !== '' && strtolower($username) !== strtolower($fromEmail)) {
            $fromEmail = $username;
        } elseif ($username !== '' && $fromEmail === '') {
            $fromEmail = $username;
        }

        $existing = $this->kvGetOn(central_db(), 'smtp_settings', []);
        $next = [
            'HR_SMTP_ENABLED' => b($raw['enabled'] ?? false) ? 'true' : 'false',
            'HR_SMTP_HOST' => s($raw['host'] ?? ''),
            'HR_SMTP_PORT' => (string) ((int) ($raw['port'] ?? 465)),
            'HR_SMTP_ENCRYPTION' => strtolower(s($raw['encryption'] ?? 'ssl', 'ssl')),
            'HR_SMTP_USERNAME' => $username,
            'HR_SMTP_PASSWORD' => $password !== '' ? $password : s($existing['HR_SMTP_PASSWORD'] ?? ''),
            'HR_SMTP_FROM_EMAIL' => $fromEmail,
            'HR_SMTP_FROM_NAME' => s($raw['fromName'] ?? 'HR Seva', 'HR Seva'),
            'HR_SMTP_REPLY_TO' => s($raw['replyTo'] ?? ''),
            'HR_SMTP_ADMIN_EMAILS' => s($raw['adminEmails'] ?? ''),
        ];

        if ($next['HR_SMTP_ENABLED'] === 'true') {
            if ($next['HR_SMTP_HOST'] === '') {
                bad('SMTP host is required');
            }
            if ((int) $next['HR_SMTP_PORT'] <= 0) {
                bad('SMTP port is required');
            }
            if ($next['HR_SMTP_USERNAME'] === '') {
                bad('SMTP username is required');
            }
            if ($next['HR_SMTP_PASSWORD'] === '') {
                bad('SMTP password is required');
            }
            if ($next['HR_SMTP_FROM_EMAIL'] === '') {
                bad('From email is required');
            }
        }

        kv_set_on(central_db(), 'smtp_settings', $next);

        return $this->get();
    }

    public function testSend(array $raw): array
    {
        require_super_admin();
        $to = s($raw['email'] ?? '');
        if ($to === '') {
            bad('Test email is required');
        }
        if (! hr_mail_is_valid_email($to)) {
            bad('Enter a valid email');
        }

        $subject = 'HR Seva SMTP Test Email';
        $html = '<div style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.6;">'
            .'<h2 style="margin:0 0 12px 0;color:#16404b;">SMTP Test Successful</h2>'
            .'<p style="margin:0 0 12px 0;">This test email was sent from the HR Seva SMTP Control module.</p>'
            .'<p style="margin:0;">If you received this email, your Hostinger SMTP configuration is working.</p>'
            .'</div>';
        $res = hr_mail_send([$to], $subject, $html);
        email_log_write('smtp_test', 'smtp_test', 0, $to, $subject, (bool) ($res['ok'] ?? false), (string) ($res['error'] ?? ''));
        if (empty($res['ok'])) {
            bad((string) ($res['error'] ?? 'Failed to send test email'));
        }

        return ['ok' => true, 'message' => 'Test email sent successfully'];
    }

    private function kvGetOn(\PDO $d, string $k, mixed $default = null): mixed
    {
        $st = $d->prepare('SELECT value FROM app_kv WHERE key=?');
        $st->execute([$k]);
        $r = $st->fetch();
        if (! $r) {
            return $default;
        }
        $v = json_decode((string) $r['value'], true);

        return ($v === null && (string) $r['value'] !== 'null') ? $default : $v;
    }
}
