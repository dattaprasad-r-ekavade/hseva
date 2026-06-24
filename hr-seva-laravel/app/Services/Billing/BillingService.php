<?php

namespace App\Services\Billing;

class BillingService
{
    public function subscriptionInfo(): array
    {
        return subscription_info_get();
    }

    public function accessTemplate(): array
    {
        return client_access_template_get();
    }

    public function billing(): array
    {
        return client_billing_get();
    }

    public function invoices(): array
    {
        return client_invoices_get();
    }
}
