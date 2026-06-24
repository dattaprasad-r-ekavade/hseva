<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Api\Concerns\ValidatesPayrollPeriod;
use App\Http\Controllers\Controller;
use App\Services\Bonus\BonusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BonusController extends Controller
{
    use RespondsWithJson;
    use ValidatesPayrollPeriod;

    public function __construct(private BonusService $bonus) {}

    public function generate(Request $request): JsonResponse
    {
        [$month, $year] = $this->periodFromRequest($request->json()->all());

        return $this->ok($this->bonus->generate($month, $year));
    }

    public function sheets(Request $request): JsonResponse
    {
        if ($request->isMethod('POST')) {
            return $this->ok($this->bonus->saveSheet($request->json()->all()));
        }

        return $this->ok($this->bonus->sheets());
    }

    public function show(string $id): JsonResponse
    {
        return $this->ok($this->bonus->show($id));
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->ok($this->bonus->destroy($id));
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->bonus->clear());
    }
}
