<?php

namespace App\Services\Shift;

class ShiftService
{
    public function dashboard(): array
    {
        $this->guard();

        return $this->withQuery([], function () {
            $ids = shift_company_ids_scope(true);

            return shift_dashboard_summary($ids);
        });
    }

    public function listShifts(array $query): array
    {
        $this->guard();

        return $this->withQuery($query, function () use ($query) {
            $ids = shift_company_ids_scope(isset($query['all']) && (string) $query['all'] === '1');
            $activeOnly = isset($query['active']) && (string) $query['active'] === '1';
            $all = [];
            foreach ($ids as $cid) {
                $d = shift_db_for_company((int) $cid);
                init_shift_schema($d);
                $all = array_merge($all, shift_master_rows($d, (int) $cid, $activeOnly));
            }

            return ['rows' => $all, 'count' => count($all)];
        });
    }

    public function createShift(array $payload): array
    {
        $this->guard();
        $cid = shift_write_company_id($payload);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);

        return ['row' => shift_master_upsert($d, $cid, $payload, false)];
    }

    public function updateShift(int $id, array $payload): array
    {
        $this->guard();
        $cid = shift_write_company_id($payload);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);

        return ['row' => shift_master_upsert($d, $cid, $payload + ['id' => $id], true)];
    }

    public function deleteShift(int $id, array $payload): array
    {
        $this->guard();
        $cid = shift_write_company_id($payload);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);
        shift_master_delete($d, $id);

        return ['status' => 'deleted'];
    }

    public function listAssignments(array $query): array
    {
        $this->guard();

        return $this->withQuery($query, function () use ($query) {
            $ids = shift_company_ids_scope(isset($query['all']) && (string) $query['all'] === '1');
            $all = [];
            foreach ($ids as $cid) {
                $d = shift_db_for_company((int) $cid);
                init_shift_schema($d);
                $all = array_merge($all, shift_assignment_rows($d, (int) $cid));
            }

            return ['rows' => $all, 'count' => count($all)];
        });
    }

    public function createAssignment(array $payload): array
    {
        $this->guard();
        $cid = shift_write_company_id($payload);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);

        return ['row' => shift_assignment_upsert($d, $cid, $payload, false)];
    }

    public function updateAssignment(int $id, array $payload): array
    {
        $this->guard();
        $cid = shift_write_company_id($payload);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);

        return ['row' => shift_assignment_upsert($d, $cid, $payload + ['id' => $id], true)];
    }

    public function deleteAssignment(int $id, array $payload): array
    {
        $this->guard();
        $cid = shift_write_company_id($payload);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);
        shift_assignment_delete($d, $id);

        return ['status' => 'deleted'];
    }

    public function listRosters(array $query): array
    {
        $this->guard();

        return $this->withQuery($query, function () use ($query) {
            $from = shift_parse_date(s($query['from'] ?? date('Y-m-d')), 'from');
            $to = shift_parse_date(s($query['to'] ?? $from), 'to');
            $ids = shift_company_ids_scope(isset($query['all']) && (string) $query['all'] === '1');
            $filters = [
                'department' => $query['department'] ?? '',
                'designation' => $query['designation'] ?? '',
                'empId' => $query['empId'] ?? '',
                'shiftCode' => $query['shiftCode'] ?? '',
            ];
            $all = [];
            foreach ($ids as $cid) {
                $d = shift_db_for_company((int) $cid);
                init_shift_schema($d);
                $all = array_merge($all, shift_roster_list($d, (int) $cid, $from, $to, $filters));
            }

            return ['rows' => $all, 'count' => count($all)];
        });
    }

    public function deleteRosterCell(array $payload): array
    {
        $this->guard();
        $cid = shift_write_company_id($payload);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);
        $empId = up($payload['empId'] ?? '');
        $rosterDate = s($payload['rosterDate'] ?? '');

        return ['status' => 'ok'] + shift_roster_delete_row($d, $empId, $rosterDate);
    }

    public function bulkDeleteRosters(array $payload): array
    {
        $this->guard();
        $cid = shift_write_company_id($payload);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);
        $rows = isset($payload['rows']) && is_array($payload['rows']) ? $payload['rows'] : [];

        return ['status' => 'ok'] + shift_roster_bulk_delete($d, $rows);
    }

    public function bulkUpsertRosters(array $payload): array
    {
        $this->guard();
        $cid = shift_write_company_id($payload);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);
        $res = shift_roster_upsert_rows($d, (array) ($payload['rows'] ?? []), shift_actor_name());

        return ['status' => 'ok'] + $res;
    }

    public function autoFillWeek(array $payload): array
    {
        $this->guard();
        $cid = shift_write_company_id($payload);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);
        $start = shift_parse_date(s($payload['weekStartDate'] ?? ''), 'weekStartDate');
        $end = shift_parse_date(s($payload['weekEndDate'] ?? gmdate('Y-m-d', strtotime($start.' +6 day UTC'))), 'weekEndDate');

        return shift_roster_autofill_week($d, $start, $end, shift_actor_name(), $payload);
    }

    public function copyPreviousWeek(array $payload): array
    {
        $this->guard();
        $cid = shift_write_company_id($payload);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);
        $start = shift_parse_date(s($payload['weekStartDate'] ?? ''), 'weekStartDate');
        $end = shift_parse_date(s($payload['weekEndDate'] ?? gmdate('Y-m-d', strtotime($start.' +6 day UTC'))), 'weekEndDate');

        return shift_roster_copy_previous_week($d, $start, $end, shift_actor_name());
    }

    public function getWeekStatus(array $query): array
    {
        $this->guard();
        $cid = shift_write_company_id([]);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);
        $start = shift_parse_date(s($query['weekStartDate'] ?? ''), 'weekStartDate');
        $end = shift_parse_date(s($query['weekEndDate'] ?? gmdate('Y-m-d', strtotime($start.' +6 day UTC'))), 'weekEndDate');

        return shift_week_status_get($d, $start, $end);
    }

    public function setWeekStatus(array $payload): array
    {
        $this->guard();
        $cid = shift_write_company_id($payload);
        $d = shift_db_for_company($cid);
        init_shift_schema($d);
        $start = shift_parse_date(s($payload['weekStartDate'] ?? ''), 'weekStartDate');
        $end = shift_parse_date(s($payload['weekEndDate'] ?? gmdate('Y-m-d', strtotime($start.' +6 day UTC'))), 'weekEndDate');
        $locked = b($payload['isLocked'] ?? false);
        $pub = s($payload['publishStatus'] ?? 'Draft', 'Draft');

        return shift_week_status_set($d, $start, $end, $locked, $pub, shift_actor_name());
    }

    public function calendarEvents(array $query): array
    {
        $this->guard();

        return $this->withQuery($query, function () use ($query) {
            $from = shift_parse_date(s($query['from'] ?? date('Y-m-01')), 'from');
            $to = shift_parse_date(s($query['to'] ?? date('Y-m-t')), 'to');
            $ids = shift_company_ids_scope(isset($query['all']) && (string) $query['all'] === '1');
            $filters = [
                'department' => $query['department'] ?? '',
                'empId' => $query['empId'] ?? '',
                'shiftCode' => $query['shiftCode'] ?? '',
            ];
            $events = [];
            $daySummaryMap = [];
            foreach ($ids as $cid) {
                $d = shift_db_for_company((int) $cid);
                init_shift_schema($d);
                $r = shift_calendar_events($d, (int) $cid, $from, $to, $filters);
                $events = array_merge($events, $r['events']);
                foreach ($r['daySummary'] as $dr) {
                    $date = (string) $dr['date'];
                    if (! isset($daySummaryMap[$date])) {
                        $daySummaryMap[$date] = ['date' => $date, 'totalScheduled' => 0, 'leaveCount' => 0, 'offCount' => 0, 'nightShiftCount' => 0];
                    }
                    $daySummaryMap[$date]['totalScheduled'] += (int) $dr['totalScheduled'];
                    $daySummaryMap[$date]['leaveCount'] += (int) $dr['leaveCount'];
                    $daySummaryMap[$date]['offCount'] += (int) $dr['offCount'];
                    $daySummaryMap[$date]['nightShiftCount'] += (int) $dr['nightShiftCount'];
                }
            }
            ksort($daySummaryMap);

            return ['events' => $events, 'daySummary' => array_values($daySummaryMap)];
        });
    }

    public function attendanceReport(array $query): array
    {
        $this->guard();

        return $this->withQuery($query, function () use ($query) {
            $from = shift_parse_date(s($query['from'] ?? date('Y-m-01')), 'from');
            $to = shift_parse_date(s($query['to'] ?? date('Y-m-t')), 'to');
            $ids = shift_company_ids_scope(isset($query['all']) && (string) $query['all'] === '1');
            $all = [];
            foreach ($ids as $cid) {
                $d = shift_db_for_company((int) $cid);
                init_shift_schema($d);
                $all = array_merge($all, shift_roster_attendance_report($d, (int) $cid, $from, $to));
            }

            return ['rows' => $all, 'count' => count($all)];
        });
    }

    public function attendanceReportCsv(array $query): string
    {
        $payload = $this->attendanceReport($query);
        $lines = ['Date,Company,Emp ID,Employee Name,Shift Code,Shift Name,Scheduled In,Scheduled Out,Actual In,Actual Out,Work Hours,Status,Late Mark,Early Exit,Overtime,Shift Mismatch'];
        foreach ($payload['rows'] as $r) {
            $lines[] = implode(',', array_map(
                static fn ($v) => '"'.str_replace('"', '""', (string) $v).'"',
                [
                    $r['date'], $r['company'], $r['empId'], $r['employeeName'], $r['shiftCode'], $r['shiftName'],
                    $r['scheduledIn'], $r['scheduledOut'], $r['actualIn'], $r['actualOut'], $r['workHours'], $r['status'],
                    $r['lateMark'] ? 'Yes' : 'No', $r['earlyExit'] ? 'Yes' : 'No', $r['overtime'], $r['shiftMismatch'] ? 'Yes' : 'No',
                ]
            ));
        }

        return implode("\n", $lines)."\n";
    }

    public function myShifts(array $query): array
    {
        $this->guard();
        $ctx = auth_ctx(true);
        $role = strtolower((string) ($ctx['role'] ?? ''));
        $cid = (int) ($ctx['clientId'] ?? 0);
        if ($cid <= 0) {
            $cid = req_client_id();
        }
        if ($cid <= 0) {
            bad('Client scope is required');
        }
        $empId = '';
        if ($role === 'employee') {
            $empId = up($ctx['empId'] ?? '');
        }
        if ($empId === '') {
            $empId = up($query['empId'] ?? '');
        }
        if ($empId === '') {
            bad('empId is required');
        }
        $from = shift_parse_date(s($query['from'] ?? date('Y-m-d')), 'from');
        $to = shift_parse_date(s($query['to'] ?? gmdate('Y-m-d', strtotime($from.' +14 day UTC'))), 'to');
        $d = shift_db_for_company($cid);
        init_shift_schema($d);

        return shift_my_roster($d, $cid, $empId, $from, $to);
    }

    private function guard(): void
    {
        shift_require_access();
    }

    private function withQuery(array $query, callable $fn): mixed
    {
        $prev = $_GET;
        $_GET = array_merge($prev, $query);
        try {
            return $fn();
        } finally {
            $_GET = $prev;
        }
    }
}
