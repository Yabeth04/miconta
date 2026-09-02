<?php
namespace App\Imports;

use App\Support\MonthCloseGuard;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Excel esperado (fila 1 = encabezados):
 * A Fecha | B Descripción | C Débito/Salida | D Crédito/Entrada | E Método pago
 *
 * Una fila es movimiento solo si la columna Fecha (A) es:
 * - DD/MM/YYYY  (ej. 27/09/2025)
 * - fecha desconocida X/XX/XXXX
 * - o un DateTime/serial de Excel
 *
 * Sin fecha real (X/...): se inserta al final.
 * Sin método de pago: se guarda como "otros".
 */
class AccountingMovementsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;

    /** @var string[] */
    public array $errors = [];

    public function __construct(
        private readonly int $userId,
    ) {}

    public function collection(Collection $rows): void
    {
        $imported      = 0;
        $skipped       = 0;
        $errors        = [];
        $batch         = [];
        $deferred      = [];
        $lastKnownDate = null;
        $now           = now();
        $closedKeys    = MonthCloseGuard::closedKeys($this->userId);

        foreach ($rows as $index => $row) {
            $excelRow = $index + 2;

            try {
                if ($this->rowIsEmpty($row)) {
                    $skipped++;
                    continue;
                }

                // Columna A–E (por header o por posición)
                $fechaRaw  = $this->valueAt($row, ['fecha'], 0);
                $fechaKind = $this->classifyFecha($fechaRaw);

                if ($fechaKind === 'none') {
                    $skipped++;
                    continue;
                }

                $concept = $this->stringOrNull($this->valueAt($row, ['concepto', 'concept', 'descripcion', 'descripción'], 1));
                $debit   = $this->parseAmount($this->valueAt($row, [
                    'debito_salida', 'debito', 'débito_salida', 'debe', 'gasto', 'salida',
                ], 2));
                $credit = $this->parseAmount($this->valueAt($row, [
                    'credito_entrada', 'credito', 'crédito_entrada', 'haber', 'ingreso', 'entrada',
                ], 3));
                // Sin método de pago → otros
                $paymentType = $this->normalizePaymentType($this->valueAt($row, [
                    'metodo_pago', 'método_pago', 'metodo', 'pago',
                ], 4));

                $hasDebit  = $debit !== null && $debit > 0;
                $hasCredit = $credit !== null && $credit > 0;

                if ($hasDebit === $hasCredit) {
                    $errors[] = "Fila {$excelRow}: debe tener monto solo en Débito o solo en Crédito.";
                    continue;
                }

                $payload = [
                    'user_id'       => $this->userId,
                    'concept'       => $concept,
                    'detail'        => null,
                    'movement_type' => $hasDebit ? 'debe' : 'haber',
                    'amount'        => $hasDebit ? $debit : $credit,
                    'payment_type'  => $paymentType,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];

                if ($fechaKind === 'known') {
                    $date = $this->parseDate($fechaRaw);

                    if (! $date) {
                        $errors[] = "Fila {$excelRow}: fecha inválida.";
                        continue;
                    }

                    $periodKey = Carbon::parse($date)->format('Y-m');
                    if (isset($closedKeys[$periodKey])) {
                        $carbon   = Carbon::parse($date);
                        $errors[] = "Fila {$excelRow}: " . MonthCloseGuard::closedMessage(
                            (int) $carbon->year,
                            (int) $carbon->month,
                        );
                        continue;
                    }

                    $lastKnownDate = $date;
                    $batch[]       = array_merge($payload, ['date' => $date]);

                    if (count($batch) >= 100) {
                        DB::table('accounting_movements')->insert($batch);
                        $imported += count($batch);
                        $batch     = [];
                    }
                } else {
                    $deferred[] = $payload;
                }
            } catch (Throwable $e) {
                $errors[] = "Fila {$excelRow}: " . $e->getMessage();
            }
        }

        if ($batch !== []) {
            DB::table('accounting_movements')->insert($batch);
            $imported += count($batch);
        }

        if ($deferred !== []) {
            $fallbackDate = $lastKnownDate ?? $now->toDateString();
            $fallbackKey  = Carbon::parse($fallbackDate)->format('Y-m');

            if (isset($closedKeys[$fallbackKey])) {
                $carbon   = Carbon::parse($fallbackDate);
                $errors[] = MonthCloseGuard::closedMessage(
                    (int) $carbon->year,
                    (int) $carbon->month,
                ) . ' No se importaron filas sin fecha concreta.';
            } else {
                foreach (array_chunk($deferred, 100) as $chunk) {
                    $rowsToInsert  = array_map(
                        static fn(array $item) => array_merge($item, ['date' => $fallbackDate]),
                        $chunk
                    );
                    DB::table('accounting_movements')->insert($rowsToInsert);
                    $imported += count($rowsToInsert);
                }
            }
        }

        if ($imported === 0 && $errors === [] && $skipped > 0) {
            $errors[] = 'No se detectaron filas con fecha en la columna A (DD/MM/YYYY o X/XX/XXXX).';
        }

        $this->imported = $imported;
        $this->errors   = array_slice($errors, 0, 20);
    }

    /**
     * known | unknown | none
     */
    private function classifyFecha(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'none';
        }

        if ($value instanceof DateTimeInterface || $value instanceof Carbon) {
            return 'known';
        }

        if (is_numeric($value)) {
            return 'known';
        }

        $text = trim((string) $value);

        // 27/09/2025
        if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}$/', $text)) {
            return 'known';
        }

        // X/XX/XXXX (fecha desconocida)
        if (preg_match('/^[xX]+[\/\-][xX]+[\/\-][xX]+$/', $text)) {
            return 'unknown';
        }

        return 'none';
    }

    private function rowIsEmpty(Collection $row): bool
    {
        return $row->filter(function ($value) {
            if ($value instanceof DateTimeInterface) {
                return true;
            }

            return $value !== null && trim((string) $value) !== '';
        })->isEmpty();
    }

    /**
     * Lee por nombre de columna; si no hay match, usa índice A=0 … E=4.
     */
    private function valueAt(Collection $row, array $keys, int $index): mixed
    {
        foreach ($keys as $key) {
            $normalized = $this->normalizeKey($key);

            foreach ($row as $header => $value) {
                if ($this->normalizeKey((string) $header) === $normalized) {
                    return $value;
                }
            }

            // match parcial: "debito_salida_xxx"
            foreach ($row as $header => $value) {
                $h = $this->normalizeKey((string) $header);
                if ($h !== '' && (str_contains($h, $normalized) || str_contains($normalized, $h))) {
                    return $value;
                }
            }
        }

        return $row->values()->values()->get($index);
    }

    private function normalizeKey(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;

        return trim($value, '_');
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : mb_substr($text, 0, 255);
    }

    private function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[₡$\s]/u', '', (string) $value) ?? '';
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);
        $normalized = preg_replace('/[^\d.-]/', '', $normalized) ?? '';

        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof DateTimeInterface) {
                return Carbon::parse($value)->format('Y-m-d');
            }

            if ($value instanceof Carbon) {
                return $value->format('Y-m-d');
            }

            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            $text = trim((string) $value);

            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $text, $m)) {
                return Carbon::createFromFormat('d/m/Y', "{$m[1]}/{$m[2]}/{$m[3]}")->format('Y-m-d');
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizePaymentType(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return 'otros';
        }

        $text = $this->normalizeKey((string) $value);

        if ($text === '') {
            return 'otros';
        }

        return match (true) {
            str_contains($text, 'sinpe')         => 'sinpe',
            str_contains($text, 'efectivo')      => 'efectivo',
            str_contains($text, 'transferencia') => 'transferencia',
            str_contains($text, 'tarjeta')       => 'tarjeta',
            default                              => 'otros',
        };
    }
}
