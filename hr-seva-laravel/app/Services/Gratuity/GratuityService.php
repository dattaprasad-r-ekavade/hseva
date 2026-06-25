<?php

namespace App\Services\Gratuity;

use App\Services\Sheets\SheetCrudService;

class GratuityService
{
    public function __construct(
        private GratuityGenerator $generator,
        private SheetCrudService $sheets,
    ) {}

    public function generate(array $payload): array
    {
        return ['sheet' => $this->generator->generate($payload)];
    }

    public function sheets(): array
    {
        return $this->sheets->index('gratuity_sheet');
    }

    public function show(string $id): array
    {
        return $this->sheets->show('gratuity_sheet', $id, 'Gratuity sheet not found');
    }

    public function destroy(string $id): array
    {
        return $this->sheets->destroy('gratuity_sheet', $id);
    }

    public function clear(): array
    {
        return $this->sheets->clear('gratuity_sheet');
    }
}
