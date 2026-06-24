<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Gratuity\GratuityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GratuityController extends Controller
{
    use RespondsWithJson;

    public function __construct(private GratuityService $gratuity) {}

    public function generate(Request $request): JsonResponse
    {
        return $this->ok($this->gratuity->generate($request->json()->all()));
    }

    public function sheets(): JsonResponse
    {
        return $this->ok($this->gratuity->sheets());
    }

    public function show(string $id): JsonResponse
    {
        return $this->ok($this->gratuity->show($id));
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->ok($this->gratuity->destroy($id));
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->gratuity->clear());
    }
}
