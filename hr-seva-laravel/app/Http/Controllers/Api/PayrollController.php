<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LegacyApiResponseException;
use App\Http\Controllers\Controller;
use App\Services\Payroll\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payroll) {}

    public function generate(Request $request): JsonResponse
    {
        $b = $request->json()->all();

        return $this->ok($this->payroll->generate((int) $b['month'], (int) $b['year'], (string) ($b['absentMode'] ?? 'LOP')));
    }

    public function sheets(): JsonResponse
    {
        return $this->ok($this->payroll->sheets());
    }

    public function show(string $id): JsonResponse
    {
        return $this->ok($this->payroll->sheet($id));
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->ok($this->payroll->deleteSheet($id));
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->payroll->clear());
    }

    public function overrides(): JsonResponse
    {
        return $this->ok($this->payroll->overrides());
    }

    public function setOverride(Request $request, string $empId): JsonResponse
    {
        return $this->ok($this->payroll->setOverride($empId, $request->json()->all()));
    }

    public function deleteOverride(string $empId): JsonResponse
    {
        return $this->ok($this->payroll->deleteOverride($empId));
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
