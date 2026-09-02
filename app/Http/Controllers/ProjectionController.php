<?php
namespace App\Http\Controllers;

use App\Models\Accounting;
use App\Models\AccountingSetting;
use App\Models\FixedPayment;
use App\Models\ProjectionSetting;
use App\Support\AnnualSalaryResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectionController extends Controller
{
    private const MONTH_NAMES = [
        1  => 'Enero',
        2  => 'Febrero',
        3  => 'Marzo',
        4  => 'Abril',
        5  => 'Mayo',
        6  => 'Junio',
        7  => 'Julio',
        8  => 'Agosto',
        9  => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    public function show(Request $request)
    {
        $validated = $request->validate([
            'mode'             => ['nullable', 'in:fixed,real'],
            'year'             => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'from_year'        => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'to_year'          => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'from_month'       => ['nullable', 'integer', 'min:1', 'max:12'],
            'to_month'         => ['nullable', 'integer', 'min:1', 'max:12'],
            'starting_balance' => ['nullable', 'numeric'],
            'monthly_salary'   => ['nullable', 'numeric', 'min:0'],
        ]);

        $mode     = $validated['mode'] ?? 'real';
        $settings = $this->settings($request);
        $range    = $this->resolveRange($validated, $mode);

        if ($range['error']) {
            return response()->json(['message' => $range['error']], 422);
        }

        return $mode === 'real'
            ? $this->showReal($request, $settings, $range, $validated)
            : $this->showFixed($request, $settings, $range, $validated);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'university_fee'    => ['required', 'numeric', 'min:0'],
            'monthly_remaining' => ['nullable', 'numeric'],
        ]);

        $settings                    = $this->settings($request);
        $settings->university_fee    = $validated['university_fee'];
        $settings->monthly_remaining = array_key_exists('monthly_remaining', $validated)
            ? $validated['monthly_remaining']
            : $settings->monthly_remaining;
        $settings->save();

        return response()->json([
            'university_fee'                => (float) $settings->university_fee,
            'monthly_remaining'             => $settings->monthly_remaining === null
                ? null
                : (float) $settings->monthly_remaining,
            'uses_fixed_payments_remaining' => $settings->monthly_remaining === null,
        ], 200);
    }

    private function resolveRange(array $validated, string $mode): array
    {
        $today = Carbon::today();
        $year  = (int) ($validated['year'] ?? $today->year);

        $fromYear  = (int) ($validated['from_year'] ?? $year);
        $toYear    = (int) ($validated['to_year'] ?? $year);
        $fromMonth = (int) ($validated['from_month'] ?? 1);
        $toMonth   = (int) ($validated['to_month'] ?? 12);

        // Anual / defaults: si no mandan from_year y es real en año actual, partir del mes de hoy
        if (
            $mode === 'real'
            && ! array_key_exists('from_month', $validated)
            && ! array_key_exists('from_year', $validated)
            && $fromYear === (int) $today->year
        ) {
            $fromMonth = (int) $today->month;
        }

        $start = Carbon::create($fromYear, $fromMonth, 1)->startOfDay();
        $end   = Carbon::create($toYear, $toMonth, 1)->startOfDay();

        if ($start->gt($end)) {
            return ['error' => 'El periodo inicial no puede ser posterior al final.'];
        }

        if ($start->diffInMonths($end) > 60) {
            return ['error' => 'El rango máximo de proyección es de 60 meses.'];
        }

        $periods = [];
        $cursor  = $start->copy();
        while ($cursor->lte($end)) {
            $periods[] = [
                'year'  => (int) $cursor->year,
                'month' => (int) $cursor->month,
            ];
            $cursor->addMonth();
        }

        return [
            'error'      => null,
            'from_year'  => $fromYear,
            'from_month' => $fromMonth,
            'to_year'    => $toYear,
            'to_month'   => $toMonth,
            'year'       => $fromYear,
            'periods'    => $periods,
            'span_years' => $fromYear !== $toYear,
        ];
    }

    private function showFixed(
        Request $request,
        ProjectionSetting $settings,
        array $range,
        array $validated,
    ) {
        $userId  = $request->user()->id;
        $paymentMonths = $this->universityPaymentMonths();
        $paymentDay    = (int) config('projection.payment_day', 15);

        $baseSources = $this->resolveFixedSources($request, $settings, $range['from_year']);
        $startingBalance = array_key_exists('starting_balance', $validated)
            ? (float) $validated['starting_balance']
            : $baseSources['account_balance'];

        $months      = [];
        $running     = $startingBalance;
        $totalRemain = 0.0;
        $totalFree   = 0.0;

        foreach ($range['periods'] as $period) {
            $year  = $period['year'];
            $month = $period['month'];
            $sources = $this->resolveFixedSources($request, $settings, $year);

            $paysUniversity = in_array($month, $paymentMonths, true);
            $remaining      = $sources['monthly_remaining'];
            $freed          = $paysUniversity ? 0.0 : $sources['university_fee'];
            $delta          = $remaining + $freed;
            $running       += $delta;
            $totalRemain   += $remaining;
            $totalFree     += $freed;

            $months[] = [
                'year'              => $year,
                'month'             => $month,
                'label'             => $this->monthLabel($year, $month, $range['span_years']),
                'pays_university'   => $paysUniversity,
                'kind'              => $paysUniversity ? 'pago' : 'libre',
                'kind_label'        => $paysUniversity
                    ? "Pago U ({$paymentDay})"
                    : 'Sin pago U',
                'monthly_remaining' => round($remaining, 2),
                'university_freed'  => round($freed, 2),
                'delta'             => round($delta, 2),
                'balance'           => round($running, 2),
            ];
        }

        return response()->json([
            'mode'                      => 'fixed',
            'year'                      => $range['from_year'],
            'from_year'                 => $range['from_year'],
            'from_month'                => $range['from_month'],
            'to_year'                   => $range['to_year'],
            'to_month'                  => $range['to_month'],
            'payment_day'               => $paymentDay,
            'university_payment_months' => $paymentMonths,
            'settings'                  => [
                'university_fee'                => $baseSources['university_fee'],
                'monthly_remaining'             => $baseSources['monthly_remaining'],
                'monthly_remaining_override'    => $settings->monthly_remaining,
                'uses_fixed_payments_remaining' => $settings->monthly_remaining === null,
            ],
            'sources'                   => [
                'account_balance'          => $baseSources['account_balance'],
                'fixed_payments_remaining' => $baseSources['fixed_payments_remaining'],
                'payday_amount'            => $baseSources['payday_amount'],
                'monthly_salary'           => $baseSources['monthly_salary'],
            ],
            'starting_balance'          => round($startingBalance, 2),
            'months'                    => $months,
            'summary'                   => [
                'months_count'            => count($months),
                'payment_months_count'    => collect($months)->where('pays_university', true)->count(),
                'free_months_count'       => collect($months)->where('pays_university', false)->count(),
                'total_monthly_remaining' => round($totalRemain, 2),
                'total_university_freed'  => round($totalFree, 2),
                'total_delta'             => round($totalRemain + $totalFree, 2),
                'ending_balance'          => round($running, 2),
            ],
        ], 200);
    }

    private function showReal(
        Request $request,
        ProjectionSetting $settings,
        array $range,
        array $validated,
    ) {
        $today   = Carbon::today();
        $sources = $this->resolveRealSources($request, $settings, $range['from_year']);

        $startingBalance = array_key_exists('starting_balance', $validated)
            ? (float) $validated['starting_balance']
            : $sources['account_balance'];

        $salaryOverride = array_key_exists('monthly_salary', $validated)
            ? round(((float) $validated['monthly_salary']) / 2, 2)
            : null;

        $paymentMonths = $this->universityPaymentMonths();
        $paymentDay    = (int) config('projection.payment_day', 15);
        $skipPast      = true;

        $months      = [];
        $running     = $startingBalance;
        $totalIn     = 0.0;
        $totalOut    = 0.0;
        $totalDelta  = 0.0;

        foreach ($range['periods'] as $period) {
            $year  = $period['year'];
            $month = $period['month'];

            $yearSources = $year === $range['from_year']
                ? $sources
                : $this->resolveRealSources($request, $settings, $year);

            $paydayAmount = $salaryOverride ?? $yearSources['payday_amount'];

            $paysUniversity = in_array($month, $paymentMonths, true);
            $primeroOut     = $yearSources['primero_expenses'];
            $segundoOut     = $this->segundoExpensesForMonth(
                $yearSources['segundo_expenses'],
                $yearSources['university_fee'],
                $yearSources['university_in_segundo'],
                $paysUniversity,
            );

            $day1 = $this->applyPayday(
                $running,
                $year,
                $month,
                1,
                $paydayAmount,
                $primeroOut,
                $today,
                $skipPast,
            );
            $running = $day1['balance'];

            $day15 = $this->applyPayday(
                $running,
                $year,
                $month,
                15,
                $paydayAmount,
                $segundoOut,
                $today,
                $skipPast,
            );
            $running = $day15['balance'];

            $income = $day1['income'] + $day15['income'];
            $expense = $day1['expense'] + $day15['expense'];
            $delta  = $income - $expense;
            $totalIn += $income;
            $totalOut += $expense;
            $totalDelta += $delta;

            $months[] = [
                'year'             => $year,
                'month'            => $month,
                'label'            => $this->monthLabel($year, $month, $range['span_years']),
                'pays_university'  => $paysUniversity,
                'kind'             => $paysUniversity ? 'pago' : 'libre',
                'kind_label'       => $paysUniversity
                    ? "Pago U ({$paymentDay})"
                    : 'Sin pago U',
                'salary_in'        => round($income, 2),
                'expenses_out'     => round($expense, 2),
                'university_freed' => round($paysUniversity ? 0.0 : (
                    $yearSources['university_in_segundo'] ? $yearSources['university_fee'] : 0.0
                ), 2),
                'primero'          => [
                    'applied' => $day1['applied'],
                    'income'  => round($day1['income'], 2),
                    'expense' => round($day1['expense'], 2),
                    'skipped' => $day1['skipped'],
                ],
                'segundo'          => [
                    'applied' => $day15['applied'],
                    'income'  => round($day15['income'], 2),
                    'expense' => round($day15['expense'], 2),
                    'skipped' => $day15['skipped'],
                ],
                'delta'            => round($delta, 2),
                'balance'          => round($running, 2),
            ];
        }

        $displayPayday = $salaryOverride ?? $sources['payday_amount'];

        return response()->json([
            'mode'                      => 'real',
            'year'                      => $range['from_year'],
            'from_year'                 => $range['from_year'],
            'from_month'                => $range['from_month'],
            'to_year'                   => $range['to_year'],
            'to_month'                  => $range['to_month'],
            'payment_day'               => $paymentDay,
            'university_payment_months' => $paymentMonths,
            'settings'                  => [
                'university_fee' => $sources['university_fee'],
            ],
            'sources'                   => [
                'account_balance'       => $sources['account_balance'],
                'payday_amount'         => $sources['payday_amount'],
                'monthly_salary'        => $sources['monthly_salary'],
                'primero_expenses'      => $sources['primero_expenses'],
                'segundo_expenses'      => $sources['segundo_expenses'],
                'university_in_segundo' => $sources['university_in_segundo'],
            ],
            'starting_balance'          => round($startingBalance, 2),
            'monthly_salary'            => round($displayPayday * 2, 2),
            'payday_amount'             => round($displayPayday, 2),
            'months'                    => $months,
            'summary'                   => [
                'months_count'         => count($months),
                'payment_months_count' => collect($months)->where('pays_university', true)->count(),
                'free_months_count'    => collect($months)->where('pays_university', false)->count(),
                'total_salary_in'      => round($totalIn, 2),
                'total_expenses_out'   => round($totalOut, 2),
                'total_delta'          => round($totalDelta, 2),
                'ending_balance'       => round($running, 2),
            ],
        ], 200);
    }

    private function monthLabel(int $year, int $month, bool $spanYears): string
    {
        $name = self::MONTH_NAMES[$month];

        return $spanYears ? "{$name} {$year}" : $name;
    }

    private function applyPayday(
        float $balance,
        int $year,
        int $month,
        int $day,
        float $paydayAmount,
        float $expenses,
        Carbon $today,
        bool $skipPast,
    ): array {
        $date = Carbon::create($year, $month, $day)->startOfDay();

        if ($skipPast && $date->lt($today)) {
            return [
                'applied' => false,
                'skipped' => true,
                'income'  => 0.0,
                'expense' => 0.0,
                'balance' => $balance,
            ];
        }

        $income  = $paydayAmount;
        $expense = $expenses;
        $next    = $balance + $income - $expense;

        return [
            'applied' => true,
            'skipped' => false,
            'income'  => $income,
            'expense' => $expense,
            'balance' => $next,
        ];
    }

    private function segundoExpensesForMonth(
        float $segundoExpenses,
        float $universityFee,
        bool $universityInSegundo,
        bool $paysUniversity,
    ): float {
        if ($paysUniversity) {
            if ($universityInSegundo) {
                return $segundoExpenses;
            }

            return $segundoExpenses + $universityFee;
        }

        if ($universityInSegundo) {
            return max(0.0, $segundoExpenses - $universityFee);
        }

        return $segundoExpenses;
    }

    private function settings(Request $request): ProjectionSetting
    {
        $defaultFee = (float) config('projection.defaults.university_fee', 110000);

        return ProjectionSetting::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'university_fee'    => $defaultFee,
                'monthly_remaining' => null,
            ],
        );
    }

    private function universityPaymentMonths(): array
    {
        return collect(config('projection.university_payment_months', []))
            ->map(fn ($m) => (int) $m)
            ->all();
    }

    private function accountBalance(int $userId): float
    {
        $opening = (float) AccountingSetting::query()->firstOrCreate(
            ['user_id' => $userId],
            ['opening_balance_main' => 0],
        )->opening_balance_main;

        $global = DB::table((new Accounting)->getTable())
            ->where('user_id', $userId)
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'debe' THEN amount ELSE 0 END), 0) as total_debe")
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'haber' THEN amount ELSE 0 END), 0) as total_haber")
            ->first();

        return $opening
            + (float) ($global->total_haber ?? 0)
            - (float) ($global->total_debe ?? 0);
    }

    private function resolveFixedSources(Request $request, ProjectionSetting $settings, int $year): array
    {
        $userId = $request->user()->id;
        $salary = AnnualSalaryResolver::forUserYear($userId, $year);
        $monthly = $salary->monthlyAmount();

        $expenses = (float) FixedPayment::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->sum('amount');

        $fixedRemaining = $monthly - $expenses;
        $monthlyRemaining = $settings->monthly_remaining !== null
            ? (float) $settings->monthly_remaining
            : $fixedRemaining;

        return [
            'university_fee'           => (float) $settings->university_fee,
            'monthly_remaining'        => $monthlyRemaining,
            'fixed_payments_remaining' => $fixedRemaining,
            'account_balance'          => $this->accountBalance($userId),
            'payday_amount'            => (float) $salary->payday_amount,
            'monthly_salary'           => $monthly,
        ];
    }

    private function resolveRealSources(Request $request, ProjectionSetting $settings, int $year): array
    {
        $userId = $request->user()->id;
        $salary  = AnnualSalaryResolver::forUserYear($userId, $year);
        $fee     = (float) $settings->university_fee;

        $items = FixedPayment::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        $primero = (float) $items->where('payment_group', 'primero')->sum('amount');
        $segundo = (float) $items->where('payment_group', 'segundo')->sum('amount');

        $universityInSegundo = $items
            ->where('payment_group', 'segundo')
            ->contains(function (FixedPayment $item) use ($fee) {
                return abs(((float) $item->amount) - $fee) < 0.01
                    || str_contains(mb_strtolower($item->description), 'univers');
            });

        return [
            'university_fee'          => $fee,
            'account_balance'         => $this->accountBalance($userId),
            'payday_amount'           => (float) $salary->payday_amount,
            'monthly_salary'          => $salary->monthlyAmount(),
            'primero_expenses'        => $primero,
            'segundo_expenses'        => $segundo,
            'university_in_segundo'   => $universityInSegundo,
        ];
    }
}
