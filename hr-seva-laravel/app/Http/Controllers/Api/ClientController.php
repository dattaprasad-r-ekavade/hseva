<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LegacyApiResponseException;
use App\Http\Controllers\Controller;
use App\Services\Clients\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(private ClientService $clients) {}

    public function index(): JsonResponse
    {
        return $this->ok($this->clients->all());
    }

    public function store(Request $request): JsonResponse
    {
        return $this->ok($this->clients->upsert($request->json()->all(), false), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $body = $request->json()->all();
        $body['id'] = $id;

        return $this->ok($this->clients->upsert($body, true));
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->ok($this->clients->delete($id));
    }

    public function clear(): JsonResponse
    {
        return $this->ok($this->clients->clear());
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
