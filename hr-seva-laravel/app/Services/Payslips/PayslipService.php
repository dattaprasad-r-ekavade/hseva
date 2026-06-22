<?php

namespace App\Services\Payslips;

class PayslipService
{
    public function generate(int $month, int $year, string $empId, string $format = 'html'): array
    {
        return ['sheet' => payslip_generate($month, $year, $empId, $format)];
    }

    public function index(): array
    {
        return ['rows' => idx('payslip_index')];
    }

    public function show(string $id): array
    {
        return ['sheet' => get_sheet(idkey('payslip', $id), 'Payslip not found')];
    }

    public function destroy(string $id): array
    {
        del_sheet('payslip', $id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        clr_sheet('payslip');

        return ['status' => 'cleared'];
    }
}
