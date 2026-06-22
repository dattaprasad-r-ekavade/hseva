<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LegacyApiResponseException;
use App\Http\Controllers\Controller;
use App\Legacy\ShiftRouteBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        return $this->bridge($request, '/api/shift/dashboard', 'GET');
    }

    public function shifts(Request $request): JsonResponse
    {
        return $this->bridge($request, '/api/shifts', $request->method());
    }

    public function shiftById(Request $request, int $id): JsonResponse
    {
        return $this->bridge($request, '/api/shifts/'.$id, $request->method());
    }

    public function assignments(Request $request): JsonResponse
    {
        return $this->bridge($request, '/api/shift-assignments', $request->method());
    }

    public function assignmentById(Request $request, int $id): JsonResponse
    {
        return $this->bridge($request, '/api/shift-assignments/'.$id, $request->method());
    }

    public function rosters(Request $request): JsonResponse
    {
        return $this->bridge($request, '/api/rosters', 'GET');
    }

    public function rosterAction(Request $request, string $action): JsonResponse
    {
        return $this->bridge($request, '/api/rosters/'.$action, 'POST');
    }

    public function weekStatus(Request $request): JsonResponse
    {
        return $this->bridge($request, '/api/rosters/week-status', $request->method());
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        return $this->bridge($request, '/api/shift-calendar/events', 'GET');
    }

    public function attendanceReport(Request $request): JsonResponse
    {
        return $this->bridge($request, '/api/roster-attendance-report', 'GET');
    }

    public function myShifts(Request $request): JsonResponse
    {
        return $this->bridge($request, '/api/my-shifts', 'GET');
    }

    private function bridge(Request $request, string $path, string $method): JsonResponse
    {
        try {
            ShiftRouteBridge::handle($request, $path, $method);
        } catch (LegacyApiResponseException $e) {
            return response()->json($e->payload, $e->status, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
