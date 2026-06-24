<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Overtime\OvertimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    use RespondsWithJson;

    public function __construct(private OvertimeService $overtime) {}

    public function index(): JsonResponse
    {
        return $this->ok($this->overtime->index());
    }

    public function store(Request $request): JsonResponse
    {
        return $this->ok($this->overtime->store($request->json()->all()), 201);
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->ok($this->overtime->destroy(urldecode($id)));
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->overtime->clear());
    }
}
