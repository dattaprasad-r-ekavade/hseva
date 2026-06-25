<?php

namespace App\Services\Sheets;

use App\Services\Storage\SheetStorageService;

class SheetCrudService
{
    public function __construct(private SheetStorageService $sheets) {}

    public function index(string $sheetType): array
    {
        return ['rows' => $this->sheets->index($sheetType)];
    }

    public function show(string $sheetType, string $id, string $notFoundMessage): array
    {
        $sheet = $this->sheets->get($sheetType, $id);
        if (! is_array($sheet)) {
            nf($notFoundMessage);
        }

        return ['sheet' => $sheet];
    }

    public function destroy(string $sheetType, string $id): array
    {
        $this->sheets->delete($sheetType, $id);

        return ['status' => 'deleted'];
    }

    public function clear(string $sheetType): array
    {
        $this->sheets->clear($sheetType);

        return ['status' => 'cleared'];
    }

    public function findPeriod(string $sheetType, int $month, int $year): ?array
    {
        return find_period($this->sheets->index($sheetType), $month, $year);
    }

    public function getByPeriod(string $sheetType, int $month, int $year, string $notFoundMessage): array
    {
        $item = $this->findPeriod($sheetType, $month, $year);
        if (! $item) {
            nf($notFoundMessage);
        }

        return $this->show($sheetType, (string) $item['id'], $notFoundMessage)['sheet'];
    }
}
