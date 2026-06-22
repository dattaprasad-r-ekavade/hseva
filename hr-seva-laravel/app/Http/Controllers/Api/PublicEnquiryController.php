<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\PublicEnquiry\PublicEnquiryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicEnquiryController extends Controller
{
    use RespondsWithJson;

    public function __construct(private PublicEnquiryService $enquiries) {}

    public function store(Request $request): JsonResponse
    {
        return $this->ok($this->enquiries->store($request->json()->all()), 201);
    }
}
