<?php

namespace App\Services\PublicEnquiry;

class PublicEnquiryService
{
    public function store(array $payload): array
    {
        return ['row' => public_enquiry_create($payload)];
    }
}
