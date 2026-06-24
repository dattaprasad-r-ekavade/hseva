<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Exceptions\LegacyApiResponseException;
use Illuminate\Http\JsonResponse;

trait RespondsWithJson
{
    protected function ok(mixed $payload, int $status = 200): JsonResponse
    {
        try {
            return response()->json($payload, $status, [], JSON_UNESCAPED_UNICODE);
        } catch (LegacyApiResponseException $e) {
            return response()->json($e->payload, $e->status, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
