<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Advances\AdvanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvanceController extends Controller
{
    use RespondsWithJson;

    public function __construct(private AdvanceService $advances) {}

    public function index(Request $request): JsonResponse
    {
        return $this->ok($this->advances->index((bool) $request->boolean('outstanding')));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->ok($this->advances->store($request->json()->all()), 201);
    }

    public function eligibility(Request $request): JsonResponse
    {
        return $this->ok($this->advances->eligibility(
            up($request->query('empId', '')),
            s($request->query('date', gmdate('Y-m-d')))
        ));
    }

    public function history(): JsonResponse
    {
        return $this->ok($this->advances->history());
    }

    public function show(string $id): JsonResponse
    {
        return $this->ok($this->advances->show(urldecode($id)));
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->ok($this->advances->destroy(urldecode($id)));
    }
}
