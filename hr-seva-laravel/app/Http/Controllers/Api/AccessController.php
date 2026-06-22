<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Access\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessController extends Controller
{
    use RespondsWithJson;

    public function __construct(private AccessService $access) {}

    public function showClientAccess(int $id): JsonResponse
    {
        return $this->ok($this->access->getClientAccess($id));
    }

    public function updateClientAccess(Request $request, int $id): JsonResponse
    {
        return $this->ok($this->access->putClientAccess($id, $request->json()->all()));
    }

    public function accessTypes(Request $request): JsonResponse
    {
        if ($request->isMethod('POST')) {
            return $this->ok($this->access->createAccessType($request->json()->all()), 201);
        }

        return $this->ok($this->access->accessTypes());
    }

    public function updateAccessType(Request $request, string $code): JsonResponse
    {
        return $this->ok($this->access->updateAccessType(strtolower(urldecode($code)), $request->json()->all()));
    }

    public function destroyAccessType(string $code): JsonResponse
    {
        return $this->ok($this->access->deleteAccessType(strtolower(urldecode($code))));
    }

    public function staffRoles(Request $request): JsonResponse
    {
        $clientId = req_client_id();
        if ($clientId <= 0) {
            bad('clientId is required');
        }
        if ($request->isMethod('POST')) {
            return $this->ok($this->access->createStaffRole($clientId, $request->json()->all()), 201);
        }

        return $this->ok($this->access->staffRoles($clientId));
    }

    public function updateStaffRole(Request $request, string $code): JsonResponse
    {
        $clientId = req_client_id();
        if ($clientId <= 0) {
            bad('clientId is required');
        }

        return $this->ok($this->access->updateStaffRole($clientId, strtolower(urldecode($code)), $request->json()->all()));
    }

    public function destroyStaffRole(string $code): JsonResponse
    {
        $clientId = req_client_id();
        if ($clientId <= 0) {
            bad('clientId is required');
        }

        return $this->ok($this->access->deleteStaffRole($clientId, strtolower(urldecode($code))));
    }

    public function staffUsers(): JsonResponse
    {
        $clientId = req_client_id();
        if ($clientId <= 0) {
            bad('clientId is required');
        }

        return $this->ok($this->access->staffUsers($clientId));
    }

    public function upsertStaffUser(Request $request, string $empId): JsonResponse
    {
        $clientId = req_client_id();
        if ($clientId <= 0) {
            bad('clientId is required');
        }

        return $this->ok($this->access->upsertStaffUser($clientId, urldecode($empId), $request->json()->all()));
    }

    public function destroyStaffUser(string $empId): JsonResponse
    {
        $clientId = req_client_id();
        if ($clientId <= 0) {
            bad('clientId is required');
        }

        return $this->ok($this->access->deleteStaffUser($clientId, urldecode($empId)));
    }
}
