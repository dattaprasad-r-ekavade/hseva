<?php

namespace App\Services\Gratuity;

class GratuityService
{
    public function generate(array $payload): array
    {
        return ['sheet' => gratuity_generate($payload)];
    }

    public function sheets(): array
    {
        return ['rows' => idx('gratuity_sheet_index')];
    }

    public function show(string $id): array
    {
        return ['sheet' => get_sheet(idkey('gratuity_sheet', $id), 'Gratuity sheet not found')];
    }

    public function destroy(string $id): array
    {
        del_sheet('gratuity_sheet', $id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        clr_sheet('gratuity_sheet');

        return ['status' => 'cleared'];
    }
}
