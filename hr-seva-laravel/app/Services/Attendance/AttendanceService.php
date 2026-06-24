<?php

namespace App\Services\Attendance;

use App\Services\Sheets\SheetCrudService;
use App\Services\Storage\SheetStorageService;

class AttendanceService
{
    public function __construct(
        private AttendanceGenerator $generator,
        private SheetCrudService $sheets,
        private SheetStorageService $storage,
        private AttendanceDailyRepository $daily,
    ) {}

    public function dailyList(int $month, int $year): array
    {
        return $this->daily->list($month, $year);
    }

    public function dailyUpsert(int $month, int $year, array $records): array
    {
        return $this->daily->upsert($month, $year, $records);
    }

    public function generate(int $month, int $year, bool $fillDefault = true, bool $sundayWeeklyOff = true): array
    {
        return $this->generator->generate($month, $year, $fillDefault, $sundayWeeklyOff);
    }

    public function sheets(): array
    {
        return $this->sheets->index('attendance_sheet')['rows'];
    }

    public function sheet(string $id): array
    {
        return $this->sheets->show('attendance_sheet', $id, 'Attendance sheet not found')['sheet'];
    }

    public function deleteSheet(string $id): array
    {
        return $this->sheets->destroy('attendance_sheet', $id);
    }

    public function clearSheets(): array
    {
        return $this->sheets->clear('attendance_sheet');
    }

    public function clearAll(): array
    {
        $this->storage->clearAttendanceDaily();
        $this->storage->clear('attendance_sheet');

        return ['status' => 'cleared'];
    }
}
