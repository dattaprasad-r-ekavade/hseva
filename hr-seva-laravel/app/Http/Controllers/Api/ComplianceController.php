<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Compliance\ComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    use RespondsWithJson;

    public function __construct(private ComplianceService $compliance) {}

    public function tasks(Request $request): JsonResponse
    {
        $month = (int) $request->query('month', date('n'));
        $year = (int) $request->query('year', date('Y'));

        return $this->ok(['rows' => $this->compliance->tasks($month, $year)]);
    }

    public function upsertTasks(Request $request): JsonResponse
    {
        $body = $request->json()->all();
        $month = (int) ($body['month'] ?? 0);
        $year = (int) ($body['year'] ?? 0);
        $rows = $this->compliance->saveTasks($month, $year, $body['rows'] ?? []);

        return $this->ok(['rows' => $rows, 'count' => count($rows)]);
    }

    public function resetTasks(Request $request): JsonResponse
    {
        $month = (int) $request->query('month', 0);
        $year = (int) $request->query('year', 0);

        return $this->ok(['rows' => $this->compliance->resetTasks($month, $year)]);
    }

    public function clearTasks(): JsonResponse
    {
        return $this->ok($this->compliance->clearTasks());
    }

    public function challans(): JsonResponse
    {
        return $this->ok(['rows' => $this->compliance->challans()]);
    }

    public function storeChallan(Request $request): JsonResponse
    {
        return $this->ok(['row' => $this->compliance->upsertChallan($request->json()->all())], 201);
    }

    public function destroyChallan(string $id): JsonResponse
    {
        $this->compliance->deleteChallan($id);

        return $this->ok(['status' => 'deleted']);
    }

    public function clearChallans(): JsonResponse
    {
        return $this->ok($this->compliance->clearChallans());
    }
}
