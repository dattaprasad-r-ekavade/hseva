<?php

namespace App\Services\Payroll;

class PayrollService
{
    public function generate(int $month, int $year, string $absentMode = 'LOP'): array
    {
        return ['sheet' => payroll_generate($month, $year, $absentMode)];
    }

    public function sheets(): array
    {
        return ['rows' => idx('payroll_sheet_index')];
    }

    public function sheet(string $id): array
    {
        return ['sheet' => get_sheet(idkey('payroll_sheet', $id), 'Payroll sheet not found')];
    }

    public function deleteSheet(string $id): array
    {
        del_sheet('payroll_sheet', $id);

        return ['status' => 'deleted'];
    }

    public function clear(): array
    {
        clr_sheet('payroll_sheet');
        ovr_set([]);

        return ['status' => 'cleared'];
    }

    public function overrides(): array
    {
        return ['rows' => ovr_all()];
    }

    public function setOverride(string $empId, array $body): array
    {
        $all = ovr_all();
        $all[strtoupper($empId)] = [
            'gross' => array_key_exists('gross', $body) ? ($body['gross'] === null ? null : (float) $body['gross']) : null,
            'ctc' => array_key_exists('ctc', $body) ? ($body['ctc'] === null ? null : (float) $body['ctc']) : null,
            'pfAppl' => (bool) ($body['pfAppl'] ?? true),
            'esiAppl' => (bool) ($body['esiAppl'] ?? true),
            'ptAppl' => (bool) ($body['ptAppl'] ?? true),
            'lwfAppl' => (bool) ($body['lwfAppl'] ?? true),
        ];
        ovr_set($all);

        return ['row' => $all[strtoupper($empId)]];
    }

    public function deleteOverride(string $empId): array
    {
        $all = ovr_all();
        unset($all[strtoupper($empId)]);
        ovr_set($all);

        return ['status' => 'deleted'];
    }
}
