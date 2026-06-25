<?php

namespace App\Services\Dashboard;

use App\Services\Compliance\ComplianceRepository;
use App\Services\Employees\EmployeeRepository;
use App\Services\Storage\TenantSettingsService;

class DashboardRepository
{
    public function __construct(
        private EmployeeRepository $employees,
        private TenantSettingsService $settings,
        private ComplianceRepository $compliance,
    ) {}

    public function summary(int $month, int $year): array
    {
        $emps = $this->employees->all();
        $active = array_values(array_filter($emps, fn ($e) => $this->employees->isActive($e)));
        $pfc = count(array_filter($active, fn ($e) => strtolower((string) $e['pf']) === 'yes'));
        $esic = count(array_filter($active, fn ($e) => strtolower((string) $e['esi']) === 'yes'));

        $pit = find_period(idx('payroll_sheet_index'), $month, $year);
        $prows = [];
        if ($pit) {
            $sh = kv_get(idkey('payroll_sheet', (string) $pit['id']), null);
            if (is_array($sh) && is_array($sh['rows'] ?? null)) {
                $prows = $sh['rows'];
            }
        }

        $cnt = count($prows) ?: count($active);
        $avg = count($prows) ? round(array_sum(array_map(fn ($r) => f($r['paidDays'] ?? 0), $prows)) / count($prows), 1) : 0.0;
        $gross = round(array_sum(array_map(fn ($r) => f($r['earnedGross'] ?? 0), $prows)), 2);
        $ded = round(array_sum(array_map(fn ($r) => f($r['totalDeductions'] ?? 0), $prows)), 2);
        $net = round(array_sum(array_map(fn ($r) => f($r['netPayable'] ?? 0), $prows)), 2);

        $ps = 0;
        foreach (idx('payslip_index') as $p) {
            if ((int) ($p['month'] ?? 0) === $month && (int) ($p['year'] ?? 0) === $year) {
                $ps++;
            }
        }

        $period = period($month, $year);
        $alerts = $this->compliance->defaultTasks($month, $year);
        $act = [];
        foreach ([
            ['attendance_sheet_index', 'Attendance generated', 'Attendance sheet'],
            ['payroll_sheet_index', 'Payroll generated', 'Payroll sheet'],
            ['pf_sheet_index', 'PF Sheet generated', 'PF sheet'],
            ['esic_sheet_index', 'ESIC Sheet generated', 'ESIC sheet'],
        ] as $a) {
            foreach (idx($a[0]) as $r) {
                if (($r['period'] ?? '') === $period) {
                    $act[] = [
                        'title' => $a[1],
                        'detail' => $a[2].' for '.$period,
                        'at' => $r['generatedAt'] ?? now_iso(),
                    ];
                }
            }
        }
        usort($act, fn ($x, $y) => strcmp((string) $y['at'], (string) $x['at']));
        $act = array_slice($act, 0, 8);

        $pr = $this->settings->getCompanyProfile();

        return [
            'period' => $period,
            'companyName' => $pr['companyName'] ?? 'Company',
            'employees' => $cnt,
            'avgPaidDays' => $avg,
            'gross' => $gross,
            'deductions' => $ded,
            'pfCount' => $pfc,
            'esiCount' => $esic,
            'netTotal' => $net,
            'payslipCount' => $ps,
            'alerts' => $alerts,
            'activity' => $act,
        ];
    }
}
