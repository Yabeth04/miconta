<?php

namespace App\Http\Controllers;

use App\Models\AccountingSetting;
use App\Models\MonthClose;
use App\Support\MonthCloseGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonthCloseController extends Controller
{
    private const MONTH_NAMES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $closes = MonthClose::query()
            ->where('user_id', $userId)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(fn (MonthClose $close) => $this->serializeClose($close));

        return response()->json([
            'closes' => $closes,
            'current' => [
                'year'  => (int) now()->year,
                'month' => (int) now()->month,
            ],
        ], 200);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'year'  => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $year  = (int) $validated['year'];
        $month = (int) $validated['month'];
        $userId = $request->user()->id;

        $this->assertClosablePeriod($year, $month);

        $existing = MonthClose::query()
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        $snapshot = $this->buildSnapshot($userId, $year, $month);

        return response()->json([
            'year'    => $year,
            'month'   => $month,
            'label'   => $this->label($year, $month),
            'closed'  => (bool) $existing,
            'live'    => $snapshot,
            'stored'  => $existing ? $this->serializeClose($existing) : null,
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year'             => ['required', 'integer', 'min:2000', 'max:2100'],
            'month'            => ['required', 'integer', 'min:1', 'max:12'],
            'closing_balance'  => ['nullable', 'numeric'],
        ]);

        $year   = (int) $validated['year'];
        $month  = (int) $validated['month'];
        $userId = $request->user()->id;

        $this->assertClosablePeriod($year, $month);

        if (MonthCloseGuard::isClosed($userId, $year, $month)) {
            throw ValidationException::withMessages([
                'month' => MonthCloseGuard::monthLabel($year, $month).' ya está cerrado.',
            ]);
        }

        $snapshot = $this->buildSnapshot($userId, $year, $month);
        $closing  = array_key_exists('closing_balance', $validated) && $validated['closing_balance'] !== null
            ? round((float) $validated['closing_balance'], 2)
            : $snapshot['closing_balance'];

        $close = MonthClose::query()->create([
            'user_id'                  => $userId,
            'year'                     => $year,
            'month'                    => $month,
            'closing_balance'          => $closing,
            'total_haber'              => $snapshot['total_haber'],
            'total_debe'               => $snapshot['total_debe'],
            'movements_count'          => $snapshot['movements_count'],
            'opening_balance_at_close' => $snapshot['opening_balance'],
            'closed_at'                => now(),
        ]);

        return response()->json([
            'message' => MonthCloseGuard::monthLabel($year, $month).' cerrado.',
            'close'   => $this->serializeClose($close),
        ], 201);
    }

    public function destroy(Request $request, MonthClose $monthClose)
    {
        abort_unless((int) $monthClose->user_id === (int) $request->user()->id, 404);

        $year  = (int) $monthClose->year;
        $month = (int) $monthClose->month;
        $userId = $request->user()->id;

        // Al reabrir un mes, también se reabren los posteriores (el historial quedaría inconsistente).
        $deleted = MonthClose::query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($year, $month) {
                $query->where('year', '>', $year)
                    ->orWhere(function ($q) use ($year, $month) {
                        $q->where('year', $year)->where('month', '>=', $month);
                    });
            })
            ->delete();

        return response()->json([
            'message' => MonthCloseGuard::monthLabel($year, $month)
                .' reabierto'
                .($deleted > 1 ? " (y {$deleted} cierres desde ese mes)." : '.'),
            'reopened' => $deleted,
        ], 200);
    }

    private function assertClosablePeriod(int $year, int $month): void
    {
        $period = Carbon::create($year, $month, 1)->startOfMonth();
        $current = now()->startOfMonth();

        if ($period->gt($current)) {
            throw ValidationException::withMessages([
                'month' => 'No se pueden cerrar meses futuros.',
            ]);
        }
    }

    /**
     * @return array{
     *   opening_balance: float,
     *   total_haber: float,
     *   total_debe: float,
     *   movements_count: int,
     *   closing_balance: float,
     *   delta: float
     * }
     */
    private function buildSnapshot(int $userId, int $year, int $month): array
    {
        $opening = (float) AccountingSetting::query()->firstOrCreate(
            ['user_id' => $userId],
            ['opening_balance_main' => 0],
        )->opening_balance_main;

        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $before = DB::table('accounting_movements')
            ->where('user_id', $userId)
            ->whereDate('date', '<', $start)
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'debe' THEN amount ELSE 0 END), 0) as total_debe")
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'haber' THEN amount ELSE 0 END), 0) as total_haber")
            ->first();

        $monthTotals = DB::table('accounting_movements')
            ->where('user_id', $userId)
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'debe' THEN amount ELSE 0 END), 0) as total_debe")
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'haber' THEN amount ELSE 0 END), 0) as total_haber")
            ->selectRaw('COUNT(*) as count_total')
            ->first();

        $balanceBefore = $opening
            + (float) $before->total_haber
            - (float) $before->total_debe;

        $haber = (float) $monthTotals->total_haber;
        $debe  = (float) $monthTotals->total_debe;
        $delta = $haber - $debe;

        return [
            'opening_balance'  => round($opening, 2),
            'balance_before'   => round($balanceBefore, 2),
            'total_haber'      => round($haber, 2),
            'total_debe'       => round($debe, 2),
            'movements_count'  => (int) $monthTotals->count_total,
            'delta'            => round($delta, 2),
            'closing_balance'  => round($balanceBefore + $delta, 2),
        ];
    }

    private function serializeClose(MonthClose $close): array
    {
        return [
            'id'                       => $close->id,
            'year'                     => (int) $close->year,
            'month'                    => (int) $close->month,
            'label'                    => $this->label((int) $close->year, (int) $close->month),
            'closing_balance'          => (float) $close->closing_balance,
            'total_haber'              => (float) $close->total_haber,
            'total_debe'               => (float) $close->total_debe,
            'delta'                    => round((float) $close->total_haber - (float) $close->total_debe, 2),
            'movements_count'          => (int) $close->movements_count,
            'opening_balance_at_close' => (float) $close->opening_balance_at_close,
            'closed_at'                => $close->closed_at?->toIso8601String(),
        ];
    }

    private function label(int $year, int $month): string
    {
        return (self::MONTH_NAMES[$month] ?? (string) $month).' '.$year;
    }
}
