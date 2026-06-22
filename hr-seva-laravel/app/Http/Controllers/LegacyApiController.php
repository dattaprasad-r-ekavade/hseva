<?php

namespace App\Http\Controllers;

use App\Exceptions\LegacyApiResponseException;
use App\Legacy\LegacyApiKernel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegacyApiController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            LegacyApiKernel::handle($request);
        } catch (LegacyApiResponseException $e) {
            return response()->json($e->payload, $e->status, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
