<?php

namespace App\Services\Bonus;

use App\Services\Sheets\SheetCrudService;

class BonusService
{
    public function __construct(
        private BonusGenerator $generator,
        private SheetCrudService $sheets,
    ) {}

    public function generate(int $month, int $year): array
    {
        return ['sheet' => $this->generator->generatePreview($month, $year)];
    }

    public function sheets(): array
    {
        return $this->sheets->index('bonus_sheet');
    }

    public function saveSheet(array $payload): array
    {
        return ['sheet' => $this->generator->saveSheet($payload)];
    }

    public function show(string $id): array
    {
        return $this->sheets->show('bonus_sheet', $id, 'Bonus sheet not found');
    }

    public function destroy(string $id): array
    {
        return $this->sheets->destroy('bonus_sheet', $id);
    }

    public function clear(): array
    {
        return $this->sheets->clear('bonus_sheet');
    }
}
