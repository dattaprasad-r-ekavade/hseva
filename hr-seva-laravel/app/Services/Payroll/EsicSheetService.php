<?php

namespace App\Services\Payroll;

use App\Services\Sheets\SheetCrudService;

class EsicSheetService
{
    public function __construct(
        private EsicSheetGenerator $generator,
        private SheetCrudService $sheets,
    ) {}

    public function generate(int $month, int $year): array
    {
        return ['sheet' => $this->generator->generate($month, $year)];
    }

    public function sheets(): array
    {
        return $this->sheets->index('esic_sheet');
    }

    public function show(string $id): array
    {
        return $this->sheets->show('esic_sheet', $id, 'ESIC sheet not found');
    }

    public function destroy(string $id): array
    {
        return $this->sheets->destroy('esic_sheet', $id);
    }

    public function clear(): array
    {
        return $this->sheets->clear('esic_sheet');
    }
}
