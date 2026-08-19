<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * El Excel puede tener varias hojas; solo se importa "Principal".
 */
class AccountingWorkbookImport implements WithMultipleSheets, SkipsUnknownSheets
{
    public function __construct(
        private readonly string $sheetName,
        public AccountingMovementsImport $movementsImport,
    ) {}

    public function sheets(): array
    {
        return [
            $this->sheetName => $this->movementsImport,
        ];
    }

    public function onUnknownSheet($sheetName)
    {
        // Las demás hojas se ignoran a propósito.
    }
}
