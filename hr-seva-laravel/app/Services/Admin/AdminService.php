<?php

namespace App\Services\Admin;

use App\Services\Enquiries\EnquiryRepository;

class AdminService
{
    public function __construct(private EnquiryRepository $enquiries) {}

    public function enquiries(): array
    {
        return ['rows' => $this->enquiries->all()];
    }

    public function storeEnquiry(array $payload): array
    {
        return ['row' => $this->enquiries->adminCreate($payload)];
    }

    public function updateEnquiry(int $id, array $payload): array
    {
        return ['row' => $this->enquiries->update($id, $payload)];
    }

    public function destroyEnquiry(int $id): array
    {
        $this->enquiries->delete($id);

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
