<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Api\Concerns\ValidatesPayrollPeriod;
use App\Http\Controllers\Controller;
use App\Services\Payslips\PayslipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    use RespondsWithJson;
    use ValidatesPayrollPeriod;

    public function __construct(private PayslipService $payslips) {}

    public function generate(Request $request): JsonResponse
    {
        $body = $request->json()->all();
        [$month, $year] = $this->periodFromRequest($body);

        return $this->ok($this->payslips->generate(
            $month,
            $year,
            (string) ($body['empId'] ?? ''),
            (string) ($body['format'] ?? 'html')
        ));
    }

    public function index(): JsonResponse
    {
        return $this->ok($this->payslips->index());
    }

    public function show(string $id): JsonResponse
    {
        return $this->ok($this->payslips->show($id));
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->ok($this->payslips->destroy($id));
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->payslips->clear());
    }
}
