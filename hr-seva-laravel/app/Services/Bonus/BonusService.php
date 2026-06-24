<?php

namespace App\Services\Bonus;

class BonusService
{
    public function generate(int $month, int $year): array
    {
        return ['sheet' => bonus_generate_preview($month, $year)];
    }

    public function sheets(): array
    {
        return ['rows' => idx('bonus_sheet_index')];
    }

    public function saveSheet(array $payload): array
    {
        return ['sheet' => bonus_save_sheet($payload)];
    }

    public function show(string $id): array
    {
        return ['sheet' => get_sheet(idkey('bonus_sheet', $id), 'Bonus sheet not found')];
    }

    public function destroy(string $id): array
    {
        del_sheet('bonus_sheet', $id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        clr_sheet('bonus_sheet');

        return ['status' => 'cleared'];
    }
}
