<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use RespondsWithJson;

    public function __construct(private AdminService $admin) {}

    public function enquiries(Request $request): JsonResponse
    {
        if ($request->isMethod('POST')) {
            return $this->ok($this->admin->storeEnquiry($request->json()->all()), 201);
        }

        return $this->ok($this->admin->enquiries());
    }

    public function updateEnquiry(Request $request, int $id): JsonResponse
    {
        return $this->ok($this->admin->updateEnquiry($id, $request->json()->all()));
    }

    public function destroyEnquiry(int $id): JsonResponse
    {
        return $this->ok($this->admin->destroyEnquiry($id));
    }

    public function smtpSettings(Request $request): JsonResponse
    {
        if ($request->isMethod('PUT')) {
            return $this->ok($this->admin->updateSmtpSettings($request->json()->all()));
        }

        return $this->ok($this->admin->smtpSettings());
    }

    public function testSmtp(Request $request): JsonResponse
    {
        return $this->ok($this->admin->testSmtp($request->json()->all()));
    }
}
