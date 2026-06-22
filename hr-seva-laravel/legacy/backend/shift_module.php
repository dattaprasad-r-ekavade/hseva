<?php
declare(strict_types=1);

use App\Services\Shift\ShiftAccess;
use App\Services\Shift\ShiftAssignmentRepository;
use App\Services\Shift\ShiftCalendarService;
use App\Services\Shift\ShiftDashboardService;
use App\Services\Shift\ShiftMasterRepository;
use App\Services\Shift\ShiftReportService;
use App\Services\Shift\ShiftRosterRepository;
use App\Services\Shift\ShiftSchemaInstaller;
use App\Services\Shift\ShiftSupport;

function shift_module_app(string $class): object
{
    if (! function_exists('app') || ! app()->bound($class)) {
        throw new RuntimeException('Shift service unavailable: '.$class);
    }

    return app($class);
}

function init_shift_schema(PDO $d): void
{
    shift_module_app(ShiftSchemaInstaller::class)->install($d);
}

function shift_require_access(): void
{
    shift_module_app(ShiftAccess::class)->requireAccess();
}

function shift_actor_name(): string
{
    return shift_module_app(ShiftAccess::class)->actorName();
}

function shift_company_ids_scope(bool $allowAll): array
{
    return shift_module_app(ShiftAccess::class)->companyIdsScope($allowAll);
}

function shift_write_company_id(array $payload): int
{
    return shift_module_app(ShiftAccess::class)->writeCompanyId($payload);
}

function shift_db_for_company(int $companyId): PDO
{
    return shift_module_app(ShiftAccess::class)->dbForCompany($companyId);
}

function shift_company_name(int $companyId): string
{
    return shift_module_app(ShiftAccess::class)->companyName($companyId);
}

function shift_parse_date(string $v, string $name): string
{
    return shift_module_app(ShiftSupport::class)->parseDate($v, $name);
}

function shift_norm_time(?string $v): ?string
{
    return shift_module_app(ShiftSupport::class)->normTime($v);
}

function shift_duration_minutes(string $start, string $end): int
{
    return shift_module_app(ShiftSupport::class)->durationMinutes($start, $end);
}

function shift_norm_master(array $raw): array
{
    return shift_module_app(ShiftMasterRepository::class)->normalize($raw);
}

function shift_master_rows(PDO $d, int $companyId, bool $activeOnly = false): array
{
    return shift_module_app(ShiftMasterRepository::class)->rows($d, $companyId, $activeOnly);
}

function shift_master_upsert(PDO $d, int $companyId, array $raw, bool $mustExist): array
{
    return shift_module_app(ShiftMasterRepository::class)->upsert($d, $companyId, $raw, $mustExist);
}

function shift_master_delete(PDO $d, int $id): void
{
    shift_module_app(ShiftMasterRepository::class)->delete($d, $id);
}

function shift_assignment_norm(array $raw): array
{
    return shift_module_app(ShiftAssignmentRepository::class)->normalize($raw);
}

function shift_assignment_rows(PDO $d, int $companyId): array
{
    return shift_module_app(ShiftAssignmentRepository::class)->rows($d, $companyId);
}

function shift_assignment_upsert(PDO $d, int $companyId, array $raw, bool $mustExist): array
{
    return shift_module_app(ShiftAssignmentRepository::class)->upsert($d, $companyId, $raw, $mustExist);
}

function shift_assignment_delete(PDO $d, int $id): void
{
    shift_module_app(ShiftAssignmentRepository::class)->delete($d, $id);
}

function shift_roster_row_norm(array $r): array
{
    return shift_module_app(ShiftRosterRepository::class)->normalizeRow($r);
}

function shift_roster_upsert_rows(PDO $d, array $rows, string $actor): array
{
    return shift_module_app(ShiftRosterRepository::class)->upsertRows($d, $rows, $actor);
}

function shift_roster_delete_row(PDO $d, string $empId, string $rosterDate): array
{
    return shift_module_app(ShiftRosterRepository::class)->deleteRow($d, $empId, $rosterDate);
}

function shift_roster_bulk_delete(PDO $d, array $rows): array
{
    return shift_module_app(ShiftRosterRepository::class)->bulkDelete($d, $rows);
}

function shift_week_days(string $start): array
{
    return shift_module_app(ShiftSupport::class)->weekDays($start);
}

function shift_week_status_get(PDO $d, string $weekStart, string $weekEnd): array
{
    return shift_module_app(ShiftRosterRepository::class)->weekStatusGet($d, $weekStart, $weekEnd);
}

function shift_week_status_set(PDO $d, string $weekStart, string $weekEnd, bool $isLocked, string $publishStatus, string $actor): array
{
    return shift_module_app(ShiftRosterRepository::class)->weekStatusSet($d, $weekStart, $weekEnd, $isLocked, $publishStatus, $actor);
}

function shift_roster_list(PDO $d, int $companyId, string $start, string $end, array $filters = []): array
{
    return shift_module_app(ShiftRosterRepository::class)->list($d, $companyId, $start, $end, $filters);
}

function shift_roster_autofill_week(PDO $d, string $weekStart, string $weekEnd, string $actor, array $filters = []): array
{
    return shift_module_app(ShiftRosterRepository::class)->autofillWeek($d, $weekStart, $weekEnd, $actor, $filters);
}

function shift_roster_copy_previous_week(PDO $d, string $weekStart, string $weekEnd, string $actor): array
{
    return shift_module_app(ShiftRosterRepository::class)->copyPreviousWeek($d, $weekStart, $weekEnd, $actor);
}

function shift_dt_range(string $date, ?string $start, ?string $end): array
{
    return shift_module_app(ShiftSupport::class)->dateTimeRange($date, $start, $end);
}

function shift_calendar_events(PDO $d, int $companyId, string $from, string $to, array $filters = []): array
{
    return shift_module_app(ShiftCalendarService::class)->events($d, $companyId, $from, $to, $filters);
}

function shift_attendance_status_on(PDO $d, string $date, string $empId): string
{
    return shift_module_app(ShiftReportService::class)->attendanceStatusOn($d, $date, $empId);
}

function shift_leave_dates_map(PDO $d, string $from, string $to): array
{
    return shift_module_app(ShiftReportService::class)->leaveDatesMap($d, $from, $to);
}

function shift_roster_attendance_report(PDO $d, int $companyId, string $from, string $to): array
{
    return shift_module_app(ShiftReportService::class)->attendanceReport($d, $companyId, $from, $to);
}

function shift_dashboard_summary(array $companyIds): array
{
    return shift_module_app(ShiftDashboardService::class)->summary($companyIds);
}

function shift_my_roster(PDO $d, int $companyId, string $empId, string $from, string $to): array
{
    return shift_module_app(ShiftRosterRepository::class)->myRoster($d, $companyId, $empId, $from, $to);
}

function shift_route_handle(string $path, string $method): bool
{
    return false;
}
