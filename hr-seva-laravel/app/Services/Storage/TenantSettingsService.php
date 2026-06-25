<?php

namespace App\Services\Storage;

use App\Support\HrSevaDefaults;
use App\Services\Tenant\TenantManager;

class TenantSettingsService
{
    public function __construct(private TenantManager $tenants) {}

    public function get(string $key, mixed $default = null): mixed
    {
        if (in_array($key, ['control_settings', 'company_profile'], true)) {
            $row = $this->tenants->tenant()->table('tenant_settings')->where('key', $key)->first();
            if ($row) {
                return json_decode((string) $row->value, true) ?? $default;
            }

            return $this->legacyRead($key, $default);
        }

        return $this->legacyRead($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $json = json_encode($value, JSON_UNESCAPED_UNICODE);

        if (in_array($key, ['control_settings', 'company_profile'], true)) {
            $this->tenants->tenant()->table('tenant_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $json, 'updated_at' => $now]
            );

            return;
        }

        $this->tenants->tenant()->table('app_kv')->updateOrInsert(
            ['key' => $key],
            ['value' => $json, 'updated_at' => $now]
        );
    }

    public function updatedAt(string $key): ?string
    {
        $row = $this->tenants->tenant()->table('tenant_settings')->where('key', $key)->first();

        return $row ? (string) $row->updated_at : null;
    }

    public function control(): array
    {
        return array_replace_recursive(HrSevaDefaults::CONTROL, (array) $this->get('control_settings', HrSevaDefaults::CONTROL));
    }

    public function getControlSettings(): array
    {
        $x = $this->get('control_settings', null);
        if (! is_array($x)) {
            return ['__lastSaved' => null, '__configured' => false];
        }

        return array_merge($x, [
            '__lastSaved' => $this->updatedAt('control_settings'),
            '__configured' => true,
        ]);
    }

    public function putControlSettings(array $payload): array
    {
        $this->set('control_settings', $payload);

        return array_merge(HrSevaDefaults::CONTROL, $payload, ['__lastSaved' => now_iso()]);
    }

    public function profile(): array
    {
        return array_replace_recursive(HrSevaDefaults::PROFILE, (array) $this->get('company_profile', HrSevaDefaults::PROFILE));
    }

    public function getCompanyProfile(): array
    {
        $x = $this->get('company_profile', null);
        if (! is_array($x)) {
            $cid = req_client_id();
            if ($cid > 0) {
                $c = $this->tenants->central()->table('clients')->where('id', $cid)->first();
                if ($c) {
                    return array_merge(HrSevaDefaults::PROFILE, [
                        'companyName' => (string) $c->company_name,
                        'companyAddress' => (string) $c->company_address,
                        'regNo' => (string) $c->company_reg_no,
                        'pan' => (string) $c->company_pan,
                        'tan' => (string) $c->company_tan,
                        'gstin' => (string) $c->company_gstin,
                        'contactNo' => (string) $c->company_contact_no,
                    ], ['__lastSaved' => null]);
                }
            }

            return HrSevaDefaults::PROFILE + ['__lastSaved' => null];
        }

        $row = $this->tenants->tenant()->table('app_kv')->where('key', 'company_profile')->first();
        $updated = $row ? (string) $row->updated_at : null;

        return array_merge(HrSevaDefaults::PROFILE, $x, ['__lastSaved' => $updated]);
    }

    public function putCompanyProfile(array $payload): array
    {
        $this->set('company_profile', $payload);

        return array_merge(HrSevaDefaults::PROFILE, $payload, ['__lastSaved' => now_iso()]);
    }

    private function legacyRead(string $key, mixed $default = null): mixed
    {
        $row = $this->tenants->tenant()->table('app_kv')->where('key', $key)->first();
        if (! $row) {
            return $default;
        }
        $decoded = json_decode((string) $row->value, true);

        return ($decoded === null && $row->value !== 'null') ? $default : $decoded;
    }
}
