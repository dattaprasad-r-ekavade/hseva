<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Fnf\FnfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FnfController extends Controller
{
    use RespondsWithJson;

    public function __construct(private FnfService $fnf) {}

    public function generate(Request $request): JsonResponse
    {
        return $this->ok($this->fnf->generate($request->json()->all()));
    }

    public function sheets(): JsonResponse
    {
        return $this->ok($this->fnf->sheets());
    }

    public function show(string $id): JsonResponse
    {
        return $this->ok($this->fnf->show($id));
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->ok($this->fnf->destroy($id));
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->fnf->clear());
    }
}
