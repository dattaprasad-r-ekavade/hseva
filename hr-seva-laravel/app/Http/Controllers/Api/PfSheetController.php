<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Api\Concerns\ValidatesPayrollPeriod;
use App\Http\Controllers\Controller;
use App\Services\Payroll\PfSheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PfSheetController extends Controller
{
    use RespondsWithJson;
    use ValidatesPayrollPeriod;

    public function __construct(private PfSheetService $pf) {}

    public function generate(Request $request): JsonResponse
    {
        [$month, $year] = $this->periodFromRequest($request->json()->all());

        return $this->ok($this->pf->generate($month, $year));
    }

    public function sheets(): JsonResponse
    {
        return $this->ok($this->pf->sheets());
    }

    public function show(string $id): JsonResponse
    {
        return $this->ok($this->pf->show($id));
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->ok($this->pf->destroy($id));
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->pf->clear());
    }
}
