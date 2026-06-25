<?php

namespace App\Services\Payroll;

use App\Services\Sheets\SheetCrudService;
use App\Services\Storage\SheetStorageService;

class PayrollService
{
    public function __construct(
        private PayrollGenerator $generator,
        private SheetCrudService $sheets,
        private SheetStorageService $storage,
    ) {}

    public function generate(int $month, int $year, string $absentMode = 'LOP'): array
    {
        return ['sheet' => $this->generator->generate($month, $year, $absentMode)];
    }

    public function sheets(): array
    {
        return $this->sheets->index('payroll_sheet');
    }

    public function sheet(string $id): array
    {
        return $this->sheets->show('payroll_sheet', $id, 'Payroll sheet not found');
    }

    public function deleteSheet(string $id): array
    {
        return $this->sheets->destroy('payroll_sheet', $id);
    }

    public function clear(): array
    {
        $this->sheets->clear('payroll_sheet');
        $this->storage->setPayrollOverrides([]);

        return ['status' => 'cleared'];
    }

    public function overrides(): array
    {
        return ['rows' => $this->storage->payrollOverrides()];
    }

    public function setOverride(string $empId, array $body): array
    {
        $all = $this->storage->payrollOverrides();
        $all[strtoupper($empId)] = [
            'gross' => array_key_exists('gross', $body) ? ($body['gross'] === null ? null : (float) $body['gross']) : null,
            'ctc' => array_key_exists('ctc', $body) ? ($body['ctc'] === null ? null : (float) $body['ctc']) : null,
            'pfAppl' => (bool) ($body['pfAppl'] ?? true),
            'esiAppl' => (bool) ($body['esiAppl'] ?? true),
            'ptAppl' => (bool) ($body['ptAppl'] ?? true),
            'lwfAppl' => (bool) ($body['lwfAppl'] ?? true),
        ];
        $this->storage->setPayrollOverrides($all);

        return ['row' => $all[strtoupper($empId)]];
    }

    public function deleteOverride(string $empId): array
    {
        $all = $this->storage->payrollOverrides();
        unset($all[strtoupper($empId)]);
        $this->storage->setPayrollOverrides($all);

        return ['status' => 'deleted'];
    }
}
