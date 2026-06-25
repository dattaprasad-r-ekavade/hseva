<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LegacyApiResponseException;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardRepository $dashboard) {}

    public function summary(Request $request): JsonResponse
    {
        try {
            return response()->json(
                $this->dashboard->summary(
                    (int) $request->query('month', date('n')),
                    (int) $request->query('year', date('Y'))
                ),
                200,
                [],
                JSON_UNESCAPED_UNICODE
            );
        } catch (LegacyApiResponseException $e) {
            return response()->json($e->payload, $e->status, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
