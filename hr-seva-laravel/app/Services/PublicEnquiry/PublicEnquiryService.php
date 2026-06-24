<?php

namespace App\Services\PublicEnquiry;

use App\Services\Enquiries\EnquiryRepository;

class PublicEnquiryService
{
    public function __construct(private EnquiryRepository $repository) {}

    public function store(array $payload): array
    {
        return ['row' => $this->repository->create($payload)];
    }
}
