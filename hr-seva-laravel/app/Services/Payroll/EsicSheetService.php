<?php

namespace App\Services\Payroll;

class EsicSheetService
{
    public function generate(int $month, int $year): array
    {
        return ['sheet' => esic_generate($month, $year)];
    }

    public function sheets(): array
    {
        return ['rows' => idx('esic_sheet_index')];
    }

    public function show(string $id): array
    {
        return ['sheet' => get_sheet(idkey('esic_sheet', $id), 'ESIC sheet not found')];
    }

    public function destroy(string $id): array
    {
        del_sheet('esic_sheet', $id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        clr_sheet('esic_sheet');

        return ['status' => 'cleared'];
    }
}
