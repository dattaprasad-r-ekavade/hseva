<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LegacyApiResponseException;
use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Shift\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShiftController extends Controller
{
    use RespondsWithJson;

    public function __construct(private ShiftService $shifts) {}

    public function dashboard(): JsonResponse
    {
        return $this->ok($this->shifts->dashboard());
    }

    public function shifts(Request $request): JsonResponse
    {
        if ($request->isMethod('GET')) {
            return $this->ok($this->shifts->listShifts($request->query()));
        }
        if ($request->isMethod('POST')) {
            return $this->ok($this->shifts->createShift($request->json()->all()), 201);
        }

        return $this->ok(['detail' => 'Method Not Allowed'], 405);
    }

    public function shiftById(Request $request, int $id): JsonResponse
    {
        if ($request->isMethod('PUT')) {
            return $this->ok($this->shifts->updateShift($id, $request->json()->all()));
        }
        if ($request->isMethod('DELETE')) {
            return $this->ok($this->shifts->deleteShift($id, $request->json()->all()));
        }

        return $this->ok(['detail' => 'Method Not Allowed'], 405);
    }

    public function assignments(Request $request): JsonResponse
    {
        if ($request->isMethod('GET')) {
            return $this->ok($this->shifts->listAssignments($request->query()));
        }
        if ($request->isMethod('POST')) {
            return $this->ok($this->shifts->createAssignment($request->json()->all()), 201);
        }

        return $this->ok(['detail' => 'Method Not Allowed'], 405);
    }

    public function assignmentById(Request $request, int $id): JsonResponse
    {
        if ($request->isMethod('PUT')) {
            return $this->ok($this->shifts->updateAssignment($id, $request->json()->all()));
        }
        if ($request->isMethod('DELETE')) {
            return $this->ok($this->shifts->deleteAssignment($id, $request->json()->all()));
        }

        return $this->ok(['detail' => 'Method Not Allowed'], 405);
    }

    public function rosters(Request $request): JsonResponse
    {
        return $this->ok($this->shifts->listRosters($request->query()));
    }

    public function rosterAction(Request $request, string $action): JsonResponse
    {
        $payload = $request->json()->all();

        return match ($action) {
            'delete-cell' => $this->ok($this->shifts->deleteRosterCell($payload)),
            'bulk-delete' => $this->ok($this->shifts->bulkDeleteRosters($payload)),
            'bulk-upsert' => $this->ok($this->shifts->bulkUpsertRosters($payload)),
            'auto-fill-week' => $this->ok($this->shifts->autoFillWeek($payload)),
            'copy-previous-week' => $this->ok($this->shifts->copyPreviousWeek($payload)),
            default => $this->ok(['detail' => 'Not Found'], 404),
        };
    }

    public function weekStatus(Request $request): JsonResponse
    {
        if ($request->isMethod('GET')) {
            return $this->ok($this->shifts->getWeekStatus($request->query()));
        }
        if ($request->isMethod('POST')) {
            return $this->ok($this->shifts->setWeekStatus($request->json()->all()));
        }

        return $this->ok(['detail' => 'Method Not Allowed'], 405);
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        return $this->ok($this->shifts->calendarEvents($request->query()));
    }

    public function attendanceReport(Request $request): JsonResponse|StreamedResponse
    {
        if ($request->query('format') === 'csv') {
            try {
                $csv = $this->shifts->attendanceReportCsv($request->query());
            } catch (LegacyApiResponseException $e) {
                return response()->json($e->payload, $e->status, [], JSON_UNESCAPED_UNICODE);
            }

            return response()->streamDownload(
                static function () use ($csv): void {
                    echo $csv;
                },
                'roster-vs-attendance.csv',
                ['Content-Type' => 'text/csv; charset=utf-8']
            );
        }

        return $this->ok($this->shifts->attendanceReport($request->query()));
    }

    public function myShifts(Request $request): JsonResponse
    {
        return $this->ok($this->shifts->myShifts($request->query()));
    }
}
