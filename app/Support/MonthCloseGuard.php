<?php
namespace App\Support;

use App\Models\MonthClose;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MonthCloseGuard
{
    private const MONTH_NAMES = [
        1 => 'Enero', 2       => 'Febrero', 3  => 'Marzo', 4      => 'Abril',
        5 => 'Mayo', 6        => 'Junio', 7    => 'Julio', 8      => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public static function isClosed(int $userId, int $year, int $month): bool
    {
        return MonthClose::query()
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->exists();
    }

    /**
     * @return array<string, true> keys like "2026-09"
     */
    public static function closedKeys(int $userId): array
    {
        return MonthClose::query()
            ->where('user_id', $userId)
            ->get(['year', 'month'])
            ->mapWithKeys(fn(MonthClose $close) => [$close->periodKey() => true])
            ->all();
    }

    public static function assertOpenForDate(int $userId, CarbonInterface | string $date): void
    {
        $carbon = $date instanceof CarbonInterface
            ? Carbon::instance($date)->startOfDay()
            : Carbon::parse($date)->startOfDay();

        if (self::isClosed($userId, (int) $carbon->year, (int) $carbon->month)) {
            throw ValidationException::withMessages([
                'date' => self::closedMessage((int) $carbon->year, (int) $carbon->month),
            ]);
        }
    }

    /**
     * @param  Collection<int, object|array>  $movements  each with date
     */
    public static function assertOpenForMovements(int $userId, Collection $movements): void
    {
        $closed = self::closedKeys($userId);

        if ($closed === []) {
            return;
        }

        foreach ($movements as $movement) {
            $raw = is_array($movement)
                ? ($movement['date'] ?? null)
                : ($movement->date ?? null);

            if ($raw === null) {
                continue;
            }

            $carbon = Carbon::parse($raw);
            $key    = $carbon->format('Y-m');

            if (isset($closed[$key])) {
                throw ValidationException::withMessages([
                    'date' => self::closedMessage((int) $carbon->year, (int) $carbon->month),
                ]);
            }
        }
    }

    public static function assertNoCloses(int $userId, string $message): void
    {
        if (MonthClose::query()->where('user_id', $userId)->exists()) {
            throw ValidationException::withMessages([
                'month' => $message,
            ]);
        }
    }

    public static function monthLabel(int $year, int $month): string
    {
        $name = self::MONTH_NAMES[$month] ?? (string) $month;

        return "{$name} {$year}";
    }

    public static function closedMessage(int $year, int $month): string
    {
        return self::monthLabel($year, $month) . ' está cerrado. Reabrilo desde el modulo de cierres para registrar o editar.';
    }
}
