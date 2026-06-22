<?php

namespace App\Services\Payroll;

class PfReturnService
{
    public function generate(int $month, int $year): array
    {
        return ['sheet' => pf_return_generate($month, $year)];
    }

    public function sheets(): array
    {
        return ['rows' => idx('pf_return_sheet_index')];
    }

    public function show(string $id): array
    {
        return ['sheet' => get_sheet(idkey('pf_return_sheet', $id), 'PF return sheet not found')];
    }

    public function destroy(string $id): array
    {
        del_sheet('pf_return_sheet', $id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        clr_sheet('pf_return_sheet');

        return ['status' => 'cleared'];
    }

    public function challans(): array
    {
        return ['rows' => pf_challan_list()];
    }

    public function storeChallan(array $payload): array
    {
        return ['row' => pf_challan_create($payload)];
    }

    public function destroyChallan(string $id): array
    {
        pf_challan_delete($id);

        return ['status' => 'deleted'];
    }

    public function clearChallans(): array
    {
        pf_challan_clear();

        return ['status' => 'cleared'];
    }
}
