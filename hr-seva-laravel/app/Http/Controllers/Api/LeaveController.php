<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Leaves\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    use RespondsWithJson;

    public function __construct(private LeaveService $leaves) {}

    public function index(Request $request): JsonResponse
    {
        return $this->ok(['rows' => $this->leaves->list(
            $request->has('month') ? (int) $request->query('month') : null,
            $request->has('year') ? (int) $request->query('year') : null,
            $request->query('leaveType'),
            $request->query('status'),
        )]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->ok(['row' => $this->leaves->upsert($request->json()->all(), false)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $body = $request->json()->all();
        $body['id'] = $id;

        return $this->ok(['row' => $this->leaves->upsert($body, true)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->leaves->delete($id);

        return $this->ok(['status' => 'deleted']);
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->leaves->clear());
    }

    public function bulkUpsert(Request $request): JsonResponse
    {
        return $this->ok($this->leaves->bulkUpsert($request->json('rows', [])));
    }

    public function summary(Request $request): JsonResponse
    {
        $month = (int) $request->query('month', 0);
        $year = (int) $request->query('year', 0);
        if ($month < 1 || $month > 12 || $year < 2000) {
            return $this->ok(['detail' => 'month/year required'], 400);
        }

        return $this->ok(['rows' => $this->leaves->summary($month, $year)]);
    }
}
