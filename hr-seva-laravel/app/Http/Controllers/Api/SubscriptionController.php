<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use RespondsWithJson;

    public function __construct(private SubscriptionService $subscriptions) {}

    public function index(): JsonResponse
    {
        return $this->ok(['rows' => $this->subscriptions->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->ok(['row' => $this->subscriptions->upsert($request->json()->all(), false)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $body = $request->json()->all();
        $body['id'] = $id;

        return $this->ok(['row' => $this->subscriptions->upsert($body, true)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->subscriptions->delete($id);

        return $this->ok(['status' => 'deleted']);
    }

    public function plans(): JsonResponse
    {
        return $this->ok(['rows' => $this->subscriptions->plans()]);
    }

    public function storePlan(Request $request): JsonResponse
    {
        return $this->ok(['row' => $this->subscriptions->upsertPlan($request->json()->all(), false)], 201);
    }

    public function updatePlan(Request $request, int $id): JsonResponse
    {
        $body = $request->json()->all();
        $body['id'] = $id;

        return $this->ok(['row' => $this->subscriptions->upsertPlan($body, true)]);
    }

    public function destroyPlan(int $id): JsonResponse
    {
        $this->subscriptions->deletePlan($id);

        return $this->ok(['status' => 'deleted']);
    }

    public function info(): JsonResponse
    {
        return $this->ok($this->subscriptions->info());
    }
}
