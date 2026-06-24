<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use RespondsWithJson;

    public function __construct(private AttendanceService $attendance) {}

    public function daily(Request $request): JsonResponse
    {
        $month = (int) $request->query('month', 0);
        $year = (int) $request->query('year', 0);
        if ($month < 1 || $month > 12 || $year < 2000) {
            return $this->ok(['detail' => 'month/year required'], 400);
        }

        return $this->ok(['rows' => $this->attendance->dailyList($month, $year)]);
    }

    public function dailyUpsert(Request $request): JsonResponse
    {
        $body = $request->json()->all();
        $month = (int) ($body['month'] ?? 0);
        $year = (int) ($body['year'] ?? 0);
        if ($month < 1 || $month > 12 || $year < 2000) {
            return $this->ok(['detail' => 'month/year required'], 400);
        }

        return $this->ok(['status' => 'ok'] + $this->attendance->dailyUpsert($month, $year, $body['records'] ?? []));
    }

    public function generate(Request $request): JsonResponse
    {
        $body = $request->json()->all();
        $month = (int) ($body['month'] ?? 0);
        $year = (int) ($body['year'] ?? 0);
        if ($month < 1 || $month > 12 || $year < 2000) {
            return $this->ok(['detail' => 'month/year required'], 400);
        }

        return $this->ok(['sheet' => $this->attendance->generate(
            $month,
            $year,
            (bool) ($body['fillDefault'] ?? true),
            (bool) ($body['sundayWeeklyOff'] ?? true),
        )]);
    }

    public function sheets(): JsonResponse
    {
        return $this->ok(['rows' => $this->attendance->sheets()]);
    }

    public function showSheet(string $id): JsonResponse
    {
        return $this->ok(['sheet' => $this->attendance->sheet($id)]);
    }

    public function destroySheet(string $id): JsonResponse
    {
        $this->attendance->deleteSheet($id);

        return $this->ok(['status' => 'deleted']);
    }

    public function clearSheets(): JsonResponse
    {
        return $this->ok($this->attendance->clearSheets());
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->attendance->clearAll());
    }
}
