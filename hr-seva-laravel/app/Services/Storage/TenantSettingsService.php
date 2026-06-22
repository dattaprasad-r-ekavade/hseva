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

    public function profile(): array
    {
        return array_replace_recursive(HrSevaDefaults::PROFILE, (array) $this->get('company_profile', HrSevaDefaults::PROFILE));
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
