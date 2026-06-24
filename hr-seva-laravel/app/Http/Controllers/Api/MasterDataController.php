<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\MasterData\MasterDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    use RespondsWithJson;

    public function __construct(private MasterDataService $masterData) {}

    public function attendanceStatuses(Request $request): JsonResponse
    {
        if ($request->isMethod('POST')) {
            return $this->ok($this->masterData->upsertAttendanceStatus($request->json()->all(), false), 201);
        }

        return $this->ok($this->masterData->attendanceStatuses((bool) $request->boolean('activeOnly')));
    }

    public function updateAttendanceStatus(Request $request, string $code): JsonResponse
    {
        $payload = $request->json()->all();
        $payload['code'] = up(urldecode($code));

        return $this->ok($this->masterData->upsertAttendanceStatus($payload, true));
    }

    public function destroyAttendanceStatus(string $code): JsonResponse
    {
        return $this->ok($this->masterData->deleteAttendanceStatus(up(urldecode($code))));
    }

    public function employeeTypes(Request $request): JsonResponse
    {
        if ($request->isMethod('POST')) {
            return $this->ok($this->masterData->upsertEmployeeType($request->json()->all(), false), 201);
        }

        return $this->ok($this->masterData->employeeTypes((bool) $request->boolean('activeOnly')));
    }

    public function updateEmployeeType(Request $request, string $code): JsonResponse
    {
        $payload = $request->json()->all();
        $payload['code'] = up(urldecode($code));

        return $this->ok($this->masterData->upsertEmployeeType($payload, true));
    }

    public function destroyEmployeeType(string $code): JsonResponse
    {
        return $this->ok($this->masterData->deleteEmployeeType(up(urldecode($code))));
    }
}
