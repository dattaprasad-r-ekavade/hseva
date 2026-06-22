<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LegacyApiResponseException;
use App\Http\Controllers\Controller;
use App\Legacy\LegacyApiKernel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HrSevaApiController extends Controller
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
