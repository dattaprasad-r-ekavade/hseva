<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Billing\BillingService;
use Illuminate\Http\JsonResponse;

class BillingController extends Controller
{
    use RespondsWithJson;

    public function __construct(private BillingService $billing) {}

    public function subscriptionInfo(): JsonResponse
    {
        return $this->ok($this->billing->subscriptionInfo());
    }

    public function accessTemplate(): JsonResponse
    {
        return $this->ok($this->billing->accessTemplate());
    }

    public function billing(): JsonResponse
    {
        return $this->ok($this->billing->billing());
    }

    public function invoices(): JsonResponse
    {
        return $this->ok($this->billing->invoices());
    }
}
