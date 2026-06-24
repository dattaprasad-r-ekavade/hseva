<?php

namespace App\Http\Controllers\Api\Concerns;

trait ValidatesPayrollPeriod
{
    protected function periodFromRequest(array $input): array
    {
        $month = (int) ($input['month'] ?? 0);
        $year = (int) ($input['year'] ?? 0);
        if ($month < 1 || $month > 12 || $year < 2000) {
            bad('month/year required');
        }

        return [$month, $year];
    }
}
