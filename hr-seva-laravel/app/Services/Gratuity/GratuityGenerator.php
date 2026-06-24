<?php

namespace App\Services\Gratuity;

use App\Services\Storage\SheetStorageService;

class GratuityGenerator
{
    public function __construct(private SheetStorageService $sheets) {}

    public function generate(array $payload): array
    {
        $clientId = req_client_id();
        $ctrl = control_get();
        $mode = gratuity_mode_norm($ctrl['gratuityMode'] ?? 'after_5yr');

        if ($mode === 'monthly') {
            $month = (int) ($payload['month'] ?? 0);
            $year = (int) ($payload['year'] ?? 0);
            if ($month < 1 || $month > 12 || $year < 2000) {
                bad('month and year are required');
            }

            $rows = [];
            foreach (employees_active_all() as $emp) {
                $calc = gratuity_calc_row($emp, $ctrl, 0.0);
                $rows[] = [
                    'empId' => (string) ($emp['id'] ?? ''),
                    'employeeName' => (string) ($emp['name'] ?? ''),
                    'dept' => (string) ($emp['dept'] ?? ''),
                    'desig' => (string) ($emp['desig'] ?? ''),
                    'basic' => $calc['basic'],
                    'da' => $calc['da'],
                    'gratuityAmount' => $calc['gratuityAmount'],
                ];
            }

            $totalAmount = round(array_sum(array_map(fn ($r) => f($r['gratuityAmount'] ?? 0), $rows)), 2);
            $sheet = $this->sheets->save('gratuity_sheet', $month, $year, $rows, [
                'mode' => 'monthly',
                'modeLabel' => gratuity_mode_label('monthly'),
                'rowCount' => count($rows),
                'totalAmount' => $totalAmount,
            ]);
            $sheet['mode'] = 'monthly';
            $sheet['modeLabel'] = gratuity_mode_label('monthly');
            $sheet['totalAmount'] = $totalAmount;
            mail_sheet_event('gratuity_sheet', $clientId, $sheet, 'Gratuity Sheet', [
                'Mode' => (string) ($sheet['modeLabel'] ?? ''),
            ]);

            return $sheet;
        }

        $eid = up($payload['empId'] ?? '');
        if ($eid === '') {
            bad('empId is required');
        }

        $years = f($payload['years'] ?? 0);
        $emp = null;
        foreach (employees_all() as $e) {
            if (($e['id'] ?? '') === $eid) {
                $emp = $e;
                break;
            }
        }
        if (! $emp) {
            nf('Employee not found');
        }

        $calc = gratuity_calc_row($emp, $ctrl, $years);
        $id = $eid.'-gratuity-'.time();
        $row = [
            'id' => $id,
            'empId' => $eid,
            'employeeName' => (string) ($emp['name'] ?? $eid),
            'dept' => (string) ($emp['dept'] ?? ''),
            'desig' => (string) ($emp['desig'] ?? ''),
            'generatedAt' => now_iso(),
        ] + $calc;

        $this->sheets->put('gratuity_sheet', $id, $row, [
            'empId' => $eid,
            'employeeName' => $row['employeeName'],
            'mode' => $row['mode'],
            'modeLabel' => $row['modeLabel'],
            'years' => $row['years'],
            'gratuityAmount' => $row['gratuityAmount'],
            'generatedAt' => $row['generatedAt'],
        ]);

        mail_sheet_event('gratuity_sheet', $clientId, $row, 'Gratuity Sheet', [
            'Employee ID' => (string) $eid,
            'Employee Name' => (string) $row['employeeName'],
            'Amount' => 'Rs '.number_format(f($row['gratuityAmount'] ?? 0), 2),
            'Mode' => (string) ($row['modeLabel'] ?? ''),
        ]);

        return $row;
    }
}
