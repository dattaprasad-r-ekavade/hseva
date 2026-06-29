<?php

namespace App\Services\Subscriptions;

use App\Services\Tenant\TenantManager;

class SubscriptionAccessService
{
    public function __construct(private TenantManager $tenants) {}

    /**
     * @return array{active: bool, reason: string, endDate: string|null}
     */
    public function accessState(int $clientId): array
    {
        if ($clientId <= 0) {
            return ['active' => false, 'reason' => 'Invalid client', 'endDate' => null];
        }

        $row = $this->tenants->central()
            ->table('subscriptions')
            ->where('client_id', $clientId)
            ->orderByDesc('end_date')
            ->orderByDesc('renewal_date')
            ->orderByDesc('id')
            ->first();

        if (! $row) {
            return ['active' => false, 'reason' => 'No active subscription found', 'endDate' => null];
        }

        $status = strtolower(trim((string) ($row->status ?? '')));
        $blockedStatuses = ['expired', 'cancelled', 'canceled', 'inactive', 'terminated', 'disabled', 'closed'];
        if (in_array($status, $blockedStatuses, true)) {
            return [
                'active' => false,
                'reason' => 'Subscription status is '.($row->status ?? 'Expired'),
                'endDate' => (string) ($row->end_date ?? ''),
            ];
        }

        $endDateRaw = trim((string) ($row->end_date ?? ''));
        if ($endDateRaw === '') {
            $endDateRaw = trim((string) ($row->renewal_date ?? ''));
        }
        if ($endDateRaw === '') {
            return ['active' => false, 'reason' => 'Subscription end date is missing', 'endDate' => null];
        }

        $endTs = strtotime($endDateRaw.' 23:59:59 UTC');
        if ($endTs === false) {
            return ['active' => false, 'reason' => 'Subscription end date is invalid', 'endDate' => $endDateRaw];
        }
        if (time() > $endTs) {
            return ['active' => false, 'reason' => 'Subscription expired on '.$endDateRaw, 'endDate' => $endDateRaw];
        }

        return ['active' => true, 'reason' => '', 'endDate' => $endDateRaw];
    }
}
