<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\FaceAttendance\FaceAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaceAttendanceController extends Controller
{
    use RespondsWithJson;

    public function __construct(private FaceAttendanceService $faceAttendance) {}

    public function settings(Request $request): JsonResponse
    {
        if ($request->isMethod('GET')) {
            return $this->ok(['row' => $this->faceAttendance->settings()]);
        }

        face_attendance_manage_ctx();

        return $this->ok(['row' => $this->faceAttendance->updateSettings($request->json()->all())]);
    }

    public function registrations(Request $request): JsonResponse
    {
        $ctx = $this->faceAttendance->viewContext();
        $scope = $this->faceAttendance->employeeScope($ctx);
        $empId = $scope !== '' ? $scope : up($request->query('employeeId', ''));

        return $this->ok([
            'rows' => $this->faceAttendance->registrations($empId !== '' ? $empId : null),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        return $this->ok(['row' => $this->faceAttendance->register($request->json()->all())], 201);
    }

    public function destroyRegistration(string $employeeId): JsonResponse
    {
        $this->faceAttendance->deleteRegistration(urldecode($employeeId));

        return $this->ok(['status' => 'deleted']);
    }

    public function scan(Request $request): JsonResponse
    {
        return $this->ok($this->faceAttendance->scan($request->json()->all()));
    }

    public function sheet(Request $request): JsonResponse
    {
        $ctx = $this->faceAttendance->viewContext();

        return $this->ok(['rows' => $this->faceAttendance->sheet($request->query(), $ctx)]);
    }

    public function report(Request $request): JsonResponse
    {
        $ctx = $this->faceAttendance->viewContext();

        return $this->ok(['rows' => $this->faceAttendance->report($request->query(), $ctx)]);
    }

    public function showAttendance(int $id): JsonResponse
    {
        $this->faceAttendance->viewContext();
        $row = $this->faceAttendance->record($id);
        if (! $row) {
            return $this->ok(['detail' => 'Attendance record not found'], 404);
        }

        return $this->ok(['row' => $row]);
    }

    public function updateAttendance(Request $request, int $id): JsonResponse
    {
        return $this->ok(['row' => $this->faceAttendance->updateRecord($id, $request->json()->all())]);
    }

    public function destroyAttendance(int $id): JsonResponse
    {
        $this->faceAttendance->deleteRecord($id);

        return $this->ok(['status' => 'deleted']);
    }

    public function myAttendance(Request $request): JsonResponse
    {
        $ctx = $this->faceAttendance->viewContext();
        if ($this->faceAttendance->employeeScope($ctx) === '') {
            return $this->ok(['detail' => 'Only employee login can use this endpoint'], 403);
        }

        return $this->ok(['rows' => $this->faceAttendance->sheet($request->query(), $ctx)]);
    }
}
