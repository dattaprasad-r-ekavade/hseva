<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Api\Concerns\ValidatesPayrollPeriod;
use App\Http\Controllers\Controller;
use App\Services\Payroll\EcrSheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcrSheetController extends Controller
{
    use RespondsWithJson;
    use ValidatesPayrollPeriod;

    public function __construct(private EcrSheetService $service) {}

    public function generate(Request $request): JsonResponse
    {
        [$month, $year] = $this->periodFromRequest($request->json()->all());
        return $this->ok($this->service->generate($month, $year));
    }

    public function sheets(): JsonResponse { return $this->ok($this->service->sheets()); }
    public function show(string $id): JsonResponse { return $this->ok($this->service->show($id)); }
    public function destroy(string $id): JsonResponse { return $this->ok($this->service->destroy($id)); }
    public function clear(): JsonResponse { return $this->ok($this->service->clear()); }
}
