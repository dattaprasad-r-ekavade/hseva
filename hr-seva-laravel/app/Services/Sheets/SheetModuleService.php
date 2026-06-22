<?php

namespace App\Services\Sheets;

class SheetModuleService
{
    public function index(string $prefix): array
    {
        return ['rows' => idx($prefix.'_index')];
    }

    public function show(string $prefix, string $id, string $notFoundMessage): array
    {
        return ['sheet' => get_sheet(idkey($prefix, $id), $notFoundMessage)];
    }

    public function destroy(string $prefix, string $id): array
    {
        del_sheet($prefix, $id);

        return ['status' => 'deleted'];
    }

    public function clear(string $prefix): array
    {
        clr_sheet($prefix);

        return ['status' => 'cleared'];
    }

    public function generate(string $generator, int $month, int $year): array
    {
        return ['sheet' => $generator($month, $year)];
    }
}
