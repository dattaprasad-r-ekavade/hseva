<?php

namespace App\Services\Billing;

use App\Services\Subscriptions\SubscriptionRepository;

class BillingService
{
    public function __construct(
        private BillingRepository $repository,
        private SubscriptionRepository $subscriptions,
    ) {}

    public function subscriptionInfo(): array
    {
        return $this->subscriptions->subscriptionInfoGet();
    }

    public function accessTemplate(): array
    {
        return $this->repository->clientAccessTemplateGet();
    }

    public function billing(): array
    {
        return $this->repository->clientBillingGet();
    }

    public function invoices(): array
    {
        return $this->repository->clientInvoicesGet();
    }
}
