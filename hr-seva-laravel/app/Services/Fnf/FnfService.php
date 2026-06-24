<?php

namespace App\Services\Fnf;

class FnfService
{
    public function generate(array $payload): array
    {
        return ['sheet' => fnf_generate($payload)];
    }

    public function sheets(): array
    {
        return ['rows' => idx('fnf_sheet_index')];
    }

    public function show(string $id): array
    {
        return ['sheet' => get_sheet(idkey('fnf_sheet', $id), 'FNF sheet not found')];
    }

    public function destroy(string $id): array
    {
        del_sheet('fnf_sheet', $id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        clr_sheet('fnf_sheet');

        return ['status' => 'cleared'];
    }
}
