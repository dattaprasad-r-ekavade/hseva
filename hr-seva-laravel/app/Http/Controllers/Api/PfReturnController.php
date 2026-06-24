<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Api\Concerns\ValidatesPayrollPeriod;
use App\Http\Controllers\Controller;
use App\Services\Payroll\PfReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PfReturnController extends Controller
{
    use RespondsWithJson;
    use ValidatesPayrollPeriod;

    public function __construct(private PfReturnService $pfReturn) {}

    public function generate(Request $request): JsonResponse
    {
        [$month, $year] = $this->periodFromRequest($request->json()->all());

        return $this->ok($this->pfReturn->generate($month, $year));
    }

    public function sheets(): JsonResponse
    {
        return $this->ok($this->pfReturn->sheets());
    }

    public function show(string $id): JsonResponse
    {
        return $this->ok($this->pfReturn->show($id));
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->ok($this->pfReturn->destroy($id));
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->pfReturn->clear());
    }

    public function challans(): JsonResponse
    {
        return $this->ok($this->pfReturn->challans());
    }

    public function storeChallan(Request $request): JsonResponse
    {
        return $this->ok($this->pfReturn->storeChallan($request->json()->all()), 201);
    }

    public function destroyChallan(string $id): JsonResponse
    {
        return $this->ok($this->pfReturn->destroyChallan($id));
    }

    public function clearChallans(): JsonResponse
    {
        return $this->ok($this->pfReturn->clearChallans());
    }
}
