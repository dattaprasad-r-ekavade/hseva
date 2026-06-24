<?php

namespace App\Services\Shift;

class ShiftService
{
    public function __construct(
        private ShiftAccess $access,
        private ShiftSchemaInstaller $schema,
        private ShiftMasterRepository $masters,
        private ShiftAssignmentRepository $assignments,
        private ShiftRosterRepository $rosters,
        private ShiftCalendarService $calendar,
        private ShiftReportService $reports,
        private ShiftDashboardService $dashboard,
        private ShiftSupport $support,
    ) {}

    public function dashboard(): array
    {
        $this->access->requireAccess();

        return $this->withQuery([], function () {
            $ids = $this->access->companyIdsScope(true);

            return $this->dashboard->summary($ids);
        });
    }

    public function listShifts(array $query): array
    {
        $this->access->requireAccess();

        return $this->withQuery($query, function () use ($query) {
            $ids = $this->access->companyIdsScope(isset($query['all']) && (string) $query['all'] === '1');
            $activeOnly = isset($query['active']) && (string) $query['active'] === '1';
            $all = [];
            foreach ($ids as $cid) {
                $d = $this->tenantDb((int) $cid);
                $all = array_merge($all, $this->masters->rows($d, (int) $cid, $activeOnly));
            }

            return ['rows' => $all, 'count' => count($all)];
        });
    }

    public function createShift(array $payload): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId($payload);
        $d = $this->tenantDb($cid);

        return ['row' => $this->masters->upsert($d, $cid, $payload, false)];
    }

    public function updateShift(int $id, array $payload): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId($payload);
        $d = $this->tenantDb($cid);

        return ['row' => $this->masters->upsert($d, $cid, $payload + ['id' => $id], true)];
    }

    public function deleteShift(int $id, array $payload): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId($payload);
        $d = $this->tenantDb($cid);
        $this->masters->delete($d, $id);

        return ['status' => 'deleted'];
    }

    public function listAssignments(array $query): array
    {
        $this->access->requireAccess();

        return $this->withQuery($query, function () use ($query) {
            $ids = $this->access->companyIdsScope(isset($query['all']) && (string) $query['all'] === '1');
            $all = [];
            foreach ($ids as $cid) {
                $d = $this->tenantDb((int) $cid);
                $all = array_merge($all, $this->assignments->rows($d, (int) $cid));
            }

            return ['rows' => $all, 'count' => count($all)];
        });
    }

    public function createAssignment(array $payload): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId($payload);
        $d = $this->tenantDb($cid);

        return ['row' => $this->assignments->upsert($d, $cid, $payload, false)];
    }

    public function updateAssignment(int $id, array $payload): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId($payload);
        $d = $this->tenantDb($cid);

        return ['row' => $this->assignments->upsert($d, $cid, $payload + ['id' => $id], true)];
    }

    public function deleteAssignment(int $id, array $payload): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId($payload);
        $d = $this->tenantDb($cid);
        $this->assignments->delete($d, $id);

        return ['status' => 'deleted'];
    }

    public function listRosters(array $query): array
    {
        $this->access->requireAccess();

        return $this->withQuery($query, function () use ($query) {
            $from = $this->support->parseDate(s($query['from'] ?? date('Y-m-d')), 'from');
            $to = $this->support->parseDate(s($query['to'] ?? $from), 'to');
            $ids = $this->access->companyIdsScope(isset($query['all']) && (string) $query['all'] === '1');
            $filters = [
                'department' => $query['department'] ?? '',
                'designation' => $query['designation'] ?? '',
                'empId' => $query['empId'] ?? '',
                'shiftCode' => $query['shiftCode'] ?? '',
            ];
            $all = [];
            foreach ($ids as $cid) {
                $d = $this->tenantDb((int) $cid);
                $all = array_merge($all, $this->rosters->list($d, (int) $cid, $from, $to, $filters));
            }

            return ['rows' => $all, 'count' => count($all)];
        });
    }

    public function deleteRosterCell(array $payload): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId($payload);
        $d = $this->tenantDb($cid);

        return ['status' => 'ok'] + $this->rosters->deleteRow($d, up($payload['empId'] ?? ''), s($payload['rosterDate'] ?? ''));
    }

    public function bulkDeleteRosters(array $payload): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId($payload);
        $d = $this->tenantDb($cid);
        $rows = isset($payload['rows']) && is_array($payload['rows']) ? $payload['rows'] : [];

        return ['status' => 'ok'] + $this->rosters->bulkDelete($d, $rows);
    }

    public function bulkUpsertRosters(array $payload): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId($payload);
        $d = $this->tenantDb($cid);
        $res = $this->rosters->upsertRows($d, (array) ($payload['rows'] ?? []), $this->access->actorName());

        return ['status' => 'ok'] + $res;
    }

    public function autoFillWeek(array $payload): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId($payload);
        $d = $this->tenantDb($cid);
        $start = $this->support->parseDate(s($payload['weekStartDate'] ?? ''), 'weekStartDate');
        $end = $this->support->parseDate(s($payload['weekEndDate'] ?? gmdate('Y-m-d', strtotime($start.' +6 day UTC'))), 'weekEndDate');

        return $this->rosters->autofillWeek($d, $start, $end, $this->access->actorName(), $payload);
    }

    public function copyPreviousWeek(array $payload): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId($payload);
        $d = $this->tenantDb($cid);
        $start = $this->support->parseDate(s($payload['weekStartDate'] ?? ''), 'weekStartDate');
        $end = $this->support->parseDate(s($payload['weekEndDate'] ?? gmdate('Y-m-d', strtotime($start.' +6 day UTC'))), 'weekEndDate');

        return $this->rosters->copyPreviousWeek($d, $start, $end, $this->access->actorName());
    }

    public function getWeekStatus(array $query): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId(['companyId' => (int) ($query['companyId'] ?? 0)]);
        $d = $this->tenantDb($cid);
        $start = $this->support->parseDate(s($query['weekStartDate'] ?? ''), 'weekStartDate');
        $end = $this->support->parseDate(s($query['weekEndDate'] ?? gmdate('Y-m-d', strtotime($start.' +6 day UTC'))), 'weekEndDate');

        return $this->rosters->weekStatusGet($d, $start, $end);
    }

    public function setWeekStatus(array $payload): array
    {
        $this->access->requireAccess();
        $cid = $this->access->writeCompanyId($payload);
        $d = $this->tenantDb($cid);
        $start = $this->support->parseDate(s($payload['weekStartDate'] ?? ''), 'weekStartDate');
        $end = $this->support->parseDate(s($payload['weekEndDate'] ?? gmdate('Y-m-d', strtotime($start.' +6 day UTC'))), 'weekEndDate');

        return $this->rosters->weekStatusSet($d, $start, $end, b($payload['isLocked'] ?? false), s($payload['publishStatus'] ?? 'Draft', 'Draft'), $this->access->actorName());
    }

    public function calendarEvents(array $query): array
    {
        $this->access->requireAccess();

        return $this->withQuery($query, function () use ($query) {
            $from = $this->support->parseDate(s($query['from'] ?? date('Y-m-01')), 'from');
            $to = $this->support->parseDate(s($query['to'] ?? date('Y-m-t')), 'to');
            $ids = $this->access->companyIdsScope(isset($query['all']) && (string) $query['all'] === '1');
            $filters = [
                'department' => $query['department'] ?? '',
                'empId' => $query['empId'] ?? '',
                'shiftCode' => $query['shiftCode'] ?? '',
            ];
            $events = [];
            $daySummaryMap = [];
            foreach ($ids as $cid) {
                $d = $this->tenantDb((int) $cid);
                $r = $this->calendar->events($d, (int) $cid, $from, $to, $filters);
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
        $this->access->requireAccess();

        return $this->withQuery($query, function () use ($query) {
            $from = $this->support->parseDate(s($query['from'] ?? date('Y-m-01')), 'from');
            $to = $this->support->parseDate(s($query['to'] ?? date('Y-m-t')), 'to');
            $ids = $this->access->companyIdsScope(isset($query['all']) && (string) $query['all'] === '1');
            $all = [];
            foreach ($ids as $cid) {
                $d = $this->tenantDb((int) $cid);
                $all = array_merge($all, $this->reports->attendanceReport($d, (int) $cid, $from, $to));
            }

            return ['rows' => $all, 'count' => count($all)];
        });
    }

    public function attendanceReportCsv(array $query): string
    {
        $payload = $this->attendanceReport($query);

        return $this->reports->attendanceReportCsv($payload['rows']);
    }

    public function myShifts(array $query): array
    {
        $this->access->requireAccess();
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
        $from = $this->support->parseDate(s($query['from'] ?? date('Y-m-d')), 'from');
        $to = $this->support->parseDate(s($query['to'] ?? gmdate('Y-m-d', strtotime($from.' +14 day UTC'))), 'to');
        $d = $this->tenantDb($cid);

        return $this->rosters->myRoster($d, $cid, $empId, $from, $to);
    }

    private function tenantDb(int $companyId): \PDO
    {
        $d = $this->access->dbForCompany($companyId);
        $this->schema->install($d);

        return $d;
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
