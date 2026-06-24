<?php

namespace App\Services\Payroll;

class EcrSheetService
{
    public function generate(int $month, int $year): array
    {
        return ['sheet' => ecr_generate($month, $year)];
    }

    public function sheets(): array
    {
        return ['rows' => idx('ecr_sheet_index')];
    }

    public function show(string $id): array
    {
        return ['sheet' => get_sheet(idkey('ecr_sheet', $id), 'ECR sheet not found')];
    }

    public function destroy(string $id): array
    {
        del_sheet('ecr_sheet', $id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        clr_sheet('ecr_sheet');

        return ['status' => 'cleared'];
    }
}
