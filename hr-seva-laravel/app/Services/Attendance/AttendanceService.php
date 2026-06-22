<?php

namespace App\Services\Attendance;

class AttendanceService
{
    public function dailyList(int $month, int $year): array
    {
        return att_daily_list($month, $year);
    }

    public function dailyUpsert(int $month, int $year, array $records): array
    {
        return att_daily_upsert($month, $year, $records);
    }

    public function generate(int $month, int $year, bool $fillDefault = true, bool $sundayWeeklyOff = true): array
    {
        return att_generate($month, $year, $fillDefault, $sundayWeeklyOff);
    }

    public function sheets(): array
    {
        return idx('attendance_sheet_index');
    }

    public function sheet(string $id): array
    {
        return get_sheet(idkey('attendance_sheet', $id), 'Attendance sheet not found');
    }

    public function deleteSheet(string $id): void
    {
        del_sheet('attendance_sheet', $id);
    }

    public function clearSheets(): array
    {
        clr_sheet('attendance_sheet');

        return ['status' => 'cleared'];
    }

    public function clearAll(): array
    {
        if (function_exists('app') && app()->bound(\App\Services\Storage\SheetStorageService::class)) {
            app(\App\Services\Storage\SheetStorageService::class)->clearAttendanceDaily();
        }
        clr_sheet('attendance_sheet');

        return ['status' => 'cleared'];
    }
}
