<?php

namespace App\Services\Admin;

class AdminService
{
    public function enquiries(): array
    {
        return ['rows' => admin_enquiries_all()];
    }

    public function storeEnquiry(array $payload): array
    {
        return ['row' => admin_enquiry_create($payload)];
    }

    public function updateEnquiry(int $id, array $payload): array
    {
        return ['row' => admin_enquiry_update($id, $payload)];
    }

    public function destroyEnquiry(int $id): array
    {
        admin_enquiry_delete($id);

        return ['status' => 'deleted'];
    }

    public function smtpSettings(): array
    {
        return ['row' => smtp_settings_get()];
    }

    public function updateSmtpSettings(array $payload): array
    {
        return ['row' => smtp_settings_put($payload)];
    }

    public function testSmtp(array $payload): array
    {
        return smtp_test_send($payload);
    }
}
