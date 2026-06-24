<?php

namespace App\Services\Clients;

class ClientService
{
    public function all(): array
    {
        return ['rows' => clients_all()];
    }

    public function upsert(array $payload, ?bool $mustExist = null): array
    {
        return ['row' => client_upsert($payload, $mustExist)];
    }

    public function delete(int $id): array
    {
        client_delete($id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        central_db()->exec('DELETE FROM clients');
        central_db()->exec('DELETE FROM client_access');

        return ['status' => 'cleared'];
    }
}
