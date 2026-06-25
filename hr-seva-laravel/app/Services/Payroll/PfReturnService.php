<?php

namespace App\Services\Payroll;

use App\Services\Sheets\SheetCrudService;

class PfReturnService
{
    public function __construct(
        private PfReturnGenerator $generator,
        private SheetCrudService $sheets,
        private StatutoryChallanRepository $challans,
    ) {}

    public function generate(int $month, int $year): array
    {
        return ['sheet' => $this->generator->generate($month, $year)];
    }

    public function sheets(): array
    {
        return $this->sheets->index('pf_return_sheet');
    }

    public function show(string $id): array
    {
        return $this->sheets->show('pf_return_sheet', $id, 'PF return sheet not found');
    }

    public function destroy(string $id): array
    {
        return $this->sheets->destroy('pf_return_sheet', $id);
    }

    public function clear(): array
    {
        return $this->sheets->clear('pf_return_sheet');
    }

    public function challans(): array
    {
        return ['rows' => $this->challans->pfList()];
    }

    public function storeChallan(array $body): array
    {
        return ['row' => $this->challans->pfCreate($body)];
    }

    public function destroyChallan(string $id): array
    {
        $this->challans->pfDelete($id);

        return ['status' => 'deleted'];
    }

    public function clearChallans(): array
    {
        $this->challans->pfClear();

        return ['status' => 'cleared'];
    }
}
