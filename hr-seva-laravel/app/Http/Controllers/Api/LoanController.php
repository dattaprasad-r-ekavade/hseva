<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Loans\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    use RespondsWithJson;

    public function __construct(private LoanService $loans) {}

    public function index(): JsonResponse
    {
        return $this->ok($this->loans->index());
    }

    public function store(Request $request): JsonResponse
    {
        return $this->ok($this->loans->store($request->json()->all()), 201);
    }

    public function show(string $loanId): JsonResponse
    {
        return $this->ok($this->loans->show(urldecode($loanId)));
    }

    public function update(Request $request, string $loanId): JsonResponse
    {
        return $this->ok($this->loans->update(urldecode($loanId), $request->json()->all()));
    }

    public function destroy(string $loanId): JsonResponse
    {
        return $this->ok($this->loans->destroy(urldecode($loanId)));
    }
}
