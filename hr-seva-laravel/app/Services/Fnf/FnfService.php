<?php

namespace App\Services\Fnf;

use App\Services\Sheets\SheetCrudService;

class FnfService
{
    public function __construct(
        private FnfGenerator $generator,
        private SheetCrudService $sheets,
    ) {}

    public function generate(array $payload): array
    {
        return ['sheet' => $this->generator->generate($payload)];
    }

    public function sheets(): array
    {
        return $this->sheets->index('fnf_sheet');
    }

    public function show(string $id): array
    {
        return $this->sheets->show('fnf_sheet', $id, 'FNF sheet not found');
    }

    public function destroy(string $id): array
    {
        return $this->sheets->destroy('fnf_sheet', $id);
    }

    public function clear(): array
    {
        return $this->sheets->clear('fnf_sheet');
    }
}
