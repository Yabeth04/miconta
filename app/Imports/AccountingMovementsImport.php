<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
 * - fecha desconocida X/XX/XXXX (o similar con X)
 *
 * Sin fecha real (placeholder X/...): se inserta al final con la última fecha válida del archivo
 * (o hoy si no hubo ninguna).
 */
class AccountingMovementsImport implements ToCollection, WithHeadingRow
{
    public function __construct(private readonly string $importId) {}

    public function collection(Collection $rows): void
    {
        $total = max($rows->count(), 1);
        $imported = 0;
        $errors = [];
        $batch = [];
        $deferred = []; // sin fecha real → al final
        $lastKnownDate = null;
        $now = now();

        $this->updateProgress([
            'status' => 'processing',
            'progress' => 0,
            'total' => $total,
            'imported' => 0,
            'message' => 'Procesando filas...',
            'errors' => [],
        ]);

        foreach ($rows as $index => $row) {
            $excelRow = $index + 2; // fila 1 = encabezados

            try {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $fechaRaw = $this->cell($row, ['fecha']);
                $fechaKind = $this->classifyFecha($fechaRaw);

                // Solo es movimiento si la col. A parece fecha conocida o desconocida
                if ($fechaKind === 'none') {
                    continue;
                }

                $description = $this->stringOrNull($this->cell($row, ['descripcion', 'descripción']));
                $debit = $this->parseAmount($this->cell($row, [
                    'debito_salida', 'debito', 'débito_salida', 'debe', 'gasto', 'salida',
                ]));
                $credit = $this->parseAmount($this->cell($row, [
                    'credito_entrada', 'credito', 'crédito_entrada', 'haber', 'ingreso', 'entrada',
                ]));
                $paymentType = $this->normalizePaymentType($this->cell($row, [
                    'metodo_pago', 'método_pago', 'metodo', 'pago',
                ]));

                $hasDebit = $debit !== null && $debit > 0;
                $hasCredit = $credit !== null && $credit > 0;

                if ($hasDebit === $hasCredit) {
                    $errors[] = "Fila {$excelRow}: debe tener monto solo en Débito o solo en Crédito.";
                    continue;
                }

                $payload = [
                    'description' => $description,
                    'movement_type' => $hasDebit ? 'debe' : 'haber',
                    'amount' => $hasDebit ? $debit : $credit,
                    'payment_type' => $paymentType,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($fechaKind === 'known') {
                    $date = $this->parseDate($fechaRaw);

                    if (! $date) {
                        $errors[] = "Fila {$excelRow}: fecha inválida.";
                        continue;
                    }

                    $lastKnownDate = $date;
                    $batch[] = array_merge($payload, ['date' => $date]);

                    if (count($batch) >= 100) {
                        DB::table('accounting')->insert($batch);
                        $imported += count($batch);
                        $batch = [];
                    }
                } else {
                    // X/XX/XXXX → se agrega al final
                    $deferred[] = $payload;
                }
            } catch (Throwable $e) {
                $errors[] = "Fila {$excelRow}: ".$e->getMessage();
            }

            $done = $index + 1;
            $this->updateProgress([
                'status' => 'processing',
                'progress' => (int) round(($done / $total) * 100),
                'total' => $total,
                'imported' => $imported + count($batch),
                'message' => "Procesando fila {$done} de {$total}...",
                'errors' => array_slice($errors, 0, 20),
            ]);
        }

        if ($batch !== []) {
            DB::table('accounting')->insert($batch);
            $imported += count($batch);
        }

        // Movimientos sin fecha real → al final
        if ($deferred !== []) {
            $fallbackDate = $lastKnownDate ?? $now->toDateString();

            foreach (array_chunk($deferred, 100) as $chunk) {
                $rowsToInsert = array_map(
                    static fn (array $item) => array_merge($item, ['date' => $fallbackDate]),
                    $chunk
                );
                DB::table('accounting')->insert($rowsToInsert);
                $imported += count($rowsToInsert);
            }
        }

        $this->updateProgress([
            'status' => $imported > 0 ? 'completed' : 'failed',
            'progress' => 100,
            'total' => $total,
            'imported' => $imported,
            'message' => $imported > 0
                ? "Importación terminada: {$imported} movimiento(s)."
                : 'No se importó ningún movimiento.',
            'errors' => array_slice($errors, 0, 20),
        ]);
    }

    /**
     * known = DD/MM/YYYY | unknown = X/XX/XXXX | none = no es movimiento
     */
    private function classifyFecha(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'none';
        }

        // Excel a veces trae la fecha como número serial
        if (is_numeric($value)) {
            return 'known';
        }

        $text = trim((string) $value);

        // 27/09/2025 o 27-09-2025
        if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}$/', $text)) {
            return 'known';
        }

        // Fecha desconocida: X/XX/XXXX, x/x/xxxx, X/XX/XXXXX, etc.
        if (preg_match('/^[xX]+[\/\-][xX]+[\/\-][xX]+$/', $text)) {
            return 'unknown';
        }

        return 'none';
    }

    private function updateProgress(array $data): void
    {
        Cache::put($this->cacheKey(), array_merge([
            'id' => $this->importId,
        ], $data), now()->addHours(2));
    }

    private function cacheKey(): string
    {
        return "accounting-import:{$this->importId}";
    }

    private function rowIsEmpty(Collection $row): bool
    {
        return $row->filter(fn ($value) => $value !== null && trim((string) $value) !== '')->isEmpty();
    }

    private function cell(Collection $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            $normalized = $this->normalizeKey($key);

            foreach ($row as $header => $value) {
                if ($this->normalizeKey((string) $header) === $normalized) {
                    return $value;
                }
            }
        }

        return null;
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
        $text = $this->normalizeKey((string) ($value ?? ''));

        return match (true) {
            str_contains($text, 'sinpe') => 'sinpe',
            str_contains($text, 'efectivo') => 'efectivo',
            str_contains($text, 'transferencia') => 'transferencia',
            str_contains($text, 'tarjeta') => 'tarjeta',
            default => 'otros',
        };
    }
}
