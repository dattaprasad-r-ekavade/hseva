<?php

namespace App\Services\Subscriptions;

class SubscriptionService
{
    public function __construct(private SubscriptionRepository $repository) {}

    public function all(): array
    {
        return $this->repository->subscriptionsAll();
    }

    public function upsert(array $body, ?bool $mustExist = null): array
    {
        return $this->repository->subscriptionUpsert($body, $mustExist);
    }

    public function delete(int $id): void
    {
        $this->repository->subscriptionDelete($id);
    }

    public function plans(): array
    {
        return $this->repository->plansAll();
    }

    public function upsertPlan(array $body, ?bool $mustExist = null): array
    {
        return $this->repository->planUpsert($body, $mustExist);
    }

    public function deletePlan(int $id): void
    {
        $this->repository->planDelete($id);
    }

    public function info(): array
    {
        return $this->repository->subscriptionInfoGet();
    }
}
