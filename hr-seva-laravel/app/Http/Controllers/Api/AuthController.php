<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LegacyApiResponseException;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    public function login(Request $request): JsonResponse
    {
        try {
            $body = $request->json()->all();

            return response()->json(
                $this->auth->login((string) ($body['username'] ?? ''), (string) ($body['password'] ?? '')),
                200,
                [],
                JSON_UNESCAPED_UNICODE
            );
        } catch (LegacyApiResponseException $e) {
            return response()->json($e->payload, $e->status, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function session(Request $request): JsonResponse
    {
        $token = null;
        $h = (string) $request->header('Authorization', '');
        if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $h, $m)) {
            $token = app(\App\Services\Auth\JwtService::class)->verify(trim($m[1]));
        }

        return response()->json($this->auth->session($token), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function forgot(Request $request): JsonResponse
    {
        try {
            $GLOBALS['__hr_legacy_request_body'] = $request->getContent();

            return response()->json(auth_forgot($request->json()->all()), 200, [], JSON_UNESCAPED_UNICODE);
        } catch (LegacyApiResponseException $e) {
            return response()->json($e->payload, $e->status, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
