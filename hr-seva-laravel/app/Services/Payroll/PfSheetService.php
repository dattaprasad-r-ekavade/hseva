<?php

namespace App\Services\Payroll;

use App\Services\Sheets\SheetCrudService;

class PfSheetService
{
    public function __construct(
        private PfSheetGenerator $generator,
        private SheetCrudService $sheets,
    ) {}

    public function generate(int $month, int $year): array
    {
        return ['sheet' => $this->generator->generate($month, $year)];
    }

    public function sheets(): array
    {
        return $this->sheets->index('pf_sheet');
    }

    public function show(string $id): array
    {
        return $this->sheets->show('pf_sheet', $id, 'PF sheet not found');
    }

    public function destroy(string $id): array
    {
        return $this->sheets->destroy('pf_sheet', $id);
    }

    public function clear(): array
    {
        return $this->sheets->clear('pf_sheet');
    }
}
