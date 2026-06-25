<?php

namespace App\Services\Clients;

class ClientService
{
    public function __construct(private ClientRepository $repository) {}

    public function all(): array
    {
        return ['rows' => $this->repository->clientsAll()];
    }

    public function upsert(array $payload, ?bool $mustExist = null): array
    {
        return ['row' => $this->repository->clientUpsert($payload, $mustExist)];
    }

    public function delete(int $id): array
    {
        $this->repository->clientDelete($id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        $this->repository->clear();

        return ['status' => 'cleared'];
    }
}
