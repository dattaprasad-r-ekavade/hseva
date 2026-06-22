<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Api\Concerns\ValidatesPayrollPeriod;
use App\Http\Controllers\Controller;
use App\Services\Payroll\EsicReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EsicReturnController extends Controller
{
    use RespondsWithJson;
    use ValidatesPayrollPeriod;

    public function __construct(private EsicReturnService $service) {}

    public function generate(Request $request): JsonResponse
    {
        [$month, $year] = $this->periodFromRequest($request->json()->all());
        return $this->ok($this->service->generate($month, $year));
    }

    public function sheets(): JsonResponse { return $this->ok($this->service->sheets()); }
    public function show(string $id): JsonResponse { return $this->ok($this->service->show($id)); }
    public function destroy(string $id): JsonResponse { return $this->ok($this->service->destroy($id)); }
    public function clear(): JsonResponse { return $this->ok($this->service->clear()); }

    public function challans(): JsonResponse
    {
        return $this->ok($this->service->challans());
    }

    public function storeChallan(Request $request): JsonResponse
    {
        return $this->ok($this->service->storeChallan($request->json()->all()), 201);
    }

    public function destroyChallan(string $id): JsonResponse
    {
        return $this->ok($this->service->destroyChallan($id));
    }

    public function clearChallans(): JsonResponse
    {
        return $this->ok($this->service->clearChallans());
    }
}
