<?php

namespace App\Services\Payroll;

use App\Services\Sheets\SheetCrudService;

class PfReturnService
{
    public function __construct(
        private PfReturnGenerator $generator,
        private SheetCrudService $sheets,
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
        return ['rows' => pf_challan_list()];
    }

    public function storeChallan(array $body): array
    {
        return ['row' => pf_challan_create($body)];
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
