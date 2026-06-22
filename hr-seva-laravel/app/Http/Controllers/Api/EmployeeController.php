<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LegacyApiResponseException;
use App\Http\Controllers\Controller;
use App\Services\Employees\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(private EmployeeService $employees) {}

    public function index(Request $request): JsonResponse
    {
        $activeOnly = filter_var($request->query('activeOnly', false), FILTER_VALIDATE_BOOLEAN);

        return $this->ok(['rows' => $this->employees->all($activeOnly)]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->ok(['row' => $this->employees->upsert($request->json()->all(), false)], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $body = $request->json()->all();
        $body['id'] = $id;

        return $this->ok(['row' => $this->employees->upsert($body, true)]);
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->ok($this->employees->delete($id));
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->employees->clear());
    }

    public function bulkUpsert(Request $request): JsonResponse
    {
        return $this->ok($this->employees->bulkUpsert($request->json('rows', [])));
    }

    private function ok(mixed $payload, int $status = 200): JsonResponse
    {
        try {
            return response()->json($payload, $status, [], JSON_UNESCAPED_UNICODE);
        } catch (LegacyApiResponseException $e) {
            return response()->json($e->payload, $e->status, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
