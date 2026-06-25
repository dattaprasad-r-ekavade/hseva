<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LegacyApiResponseException;
use App\Http\Controllers\Controller;
use App\Services\Storage\TenantSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantSettingsController extends Controller
{
    public function __construct(private TenantSettingsService $settings) {}

    public function control(Request $request): JsonResponse
    {
        if ($request->isMethod('GET')) {
            return $this->ok($this->settings->getControlSettings());
        }
        if ($request->isMethod('PUT')) {
            return $this->ok($this->settings->putControlSettings($request->json()->all()));
        }

        return $this->ok(['detail' => 'Method Not Allowed'], 405);
    }

    public function resetControl(): JsonResponse
    {
        return $this->ok($this->settings->putControlSettings(DEFAULT_CONTROL));
    }

    public function profile(Request $request): JsonResponse
    {
        if ($request->isMethod('GET')) {
            return $this->ok($this->settings->getCompanyProfile());
        }
        if ($request->isMethod('PUT')) {
            return $this->ok($this->settings->putCompanyProfile($request->json()->all()));
        }

        return $this->ok(['detail' => 'Method Not Allowed'], 405);
    }

    public function resetProfile(): JsonResponse
    {
        return $this->ok($this->settings->putCompanyProfile(DEFAULT_PROFILE));
    }

    private function ok(mixed $payload, int $status = 200): JsonResponse
    {
        try {
            return response()->json($payload, $status, [], JSON_UNESCAPED_UNICODE);
        } catch (LegacyApiResponseException $e) {
            return response()->json($e->payload, $e->status, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
