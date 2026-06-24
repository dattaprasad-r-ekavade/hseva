<?php

namespace App\Services\Payslips;

use App\Services\Sheets\SheetCrudService;

class PayslipService
{
    public function __construct(
        private PayslipGenerator $generator,
        private SheetCrudService $sheets,
    ) {}

    public function generate(int $month, int $year, string $empId, string $format = 'html'): array
    {
        return ['sheet' => $this->generator->generate($month, $year, $empId, $format)];
    }

    public function index(): array
    {
        return $this->sheets->index('payslip');
    }

    public function show(string $id): array
    {
        return $this->sheets->show('payslip', $id, 'Payslip not found');
    }

    public function destroy(string $id): array
    {
        return $this->sheets->destroy('payslip', $id);
    }

    public function clear(): array
    {
        return $this->sheets->clear('payslip');
    }
}
