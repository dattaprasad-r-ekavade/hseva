<?php

namespace App\Services\Subscriptions;

class SubscriptionService
{
    public function all(): array
    {
        return subscriptions_all();
    }

    public function upsert(array $body, ?bool $mustExist = null): array
    {
        return subscription_upsert($body, $mustExist);
    }

    public function delete(int $id): void
    {
        subscription_delete($id);
    }

    public function plans(): array
    {
        return plans_all();
    }

    public function upsertPlan(array $body, ?bool $mustExist = null): array
    {
        return plan_upsert($body, $mustExist);
    }

    public function deletePlan(int $id): void
    {
        plan_delete($id);
    }

    public function info(): array
    {
        return subscription_info_get();
    }
}
