<?php

namespace App\Services\Payroll;

class EsicReturnService
{
    public function generate(int $month, int $year): array
    {
        return ['sheet' => esic_return_generate($month, $year)];
    }

    public function sheets(): array
    {
        return ['rows' => idx('esic_return_sheet_index')];
    }

    public function show(string $id): array
    {
        return ['sheet' => get_sheet(idkey('esic_return_sheet', $id), 'ESIC return sheet not found')];
    }

    public function destroy(string $id): array
    {
        del_sheet('esic_return_sheet', $id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        clr_sheet('esic_return_sheet');

        return ['status' => 'cleared'];
    }

    public function challans(): array
    {
        return ['rows' => esic_challan_list()];
    }

    public function storeChallan(array $payload): array
    {
        return ['row' => esic_challan_create($payload)];
    }

    public function destroyChallan(string $id): array
    {
        esic_challan_delete($id);

        return ['status' => 'deleted'];
    }

    public function clearChallans(): array
    {
        esic_challan_clear();

        return ['status' => 'cleared'];
    }
}
