<?php

namespace App\Services\Payroll;

use App\Services\Sheets\SheetCrudService;

class EsicReturnService
{
    public function __construct(
        private EsicReturnGenerator $generator,
        private SheetCrudService $sheets,
        private StatutoryChallanRepository $challans,
    ) {}

    public function generate(int $month, int $year): array
    {
        return ['sheet' => $this->generator->generate($month, $year)];
    }

    public function sheets(): array
    {
        return $this->sheets->index('esic_return_sheet');
    }

    public function show(string $id): array
    {
        return $this->sheets->show('esic_return_sheet', $id, 'ESIC return sheet not found');
    }

    public function destroy(string $id): array
    {
        return $this->sheets->destroy('esic_return_sheet', $id);
    }

    public function clear(): array
    {
        return $this->sheets->clear('esic_return_sheet');
    }

    public function challans(): array
    {
        return ['rows' => $this->challans->esicList()];
    }

    public function storeChallan(array $body): array
    {
        return ['row' => $this->challans->esicCreate($body)];
    }

    public function destroyChallan(string $id): array
    {
        $this->challans->esicDelete($id);

        return ['status' => 'deleted'];
    }

    public function clearChallans(): array
    {
        $this->challans->esicClear();

        return ['status' => 'cleared'];
    }
}
