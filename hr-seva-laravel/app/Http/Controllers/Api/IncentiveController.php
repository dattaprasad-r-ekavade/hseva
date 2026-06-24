<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Incentives\IncentiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncentiveController extends Controller
{
    use RespondsWithJson;

    public function __construct(private IncentiveService $incentives) {}

    public function index(): JsonResponse
    {
        return $this->ok($this->incentives->index());
    }

    public function store(Request $request): JsonResponse
    {
        return $this->ok($this->incentives->store($request->json()->all()), 201);
    }

    public function show(string $id): JsonResponse
    {
        return $this->ok($this->incentives->show($id));
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->ok($this->incentives->destroy($id));
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->incentives->clear());
    }
}
