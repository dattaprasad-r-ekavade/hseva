<?php

namespace App\Services\Payroll;

class PfSheetService
{
    public function generate(int $month, int $year): array
    {
        return ['sheet' => pf_generate($month, $year)];
    }

    public function sheets(): array
    {
        return ['rows' => idx('pf_sheet_index')];
    }

    public function show(string $id): array
    {
        return ['sheet' => get_sheet(idkey('pf_sheet', $id), 'PF sheet not found')];
    }

    public function destroy(string $id): array
    {
        del_sheet('pf_sheet', $id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        clr_sheet('pf_sheet');

        return ['status' => 'cleared'];
    }
}
