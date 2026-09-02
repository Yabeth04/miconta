<?php
namespace App\Http\Controllers;

use App\Models\Accounting;
use App\Models\AccountingSetting;
use App\Models\FixedPayment;
use App\Models\FixedPaymentSetting;
use App\Models\ProjectionSetting;
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
            'year'             => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'from_month'       => ['nullable', 'integer', 'min:1', 'max:12'],
            'to_month'         => ['nullable', 'integer', 'min:1', 'max:12'],
            'starting_balance' => ['nullable', 'numeric'],
        ]);

        $year       = (int) ($validated['year'] ?? now()->year);
        $fromMonth  = (int) ($validated['from_month'] ?? 1);
        $toMonth    = (int) ($validated['to_month'] ?? 12);
        $settings   = $this->settings($request);
        $sources    = $this->resolveSources($request, $settings);

        if ($fromMonth > $toMonth) {
            return response()->json([
                'message' => 'El mes inicial no puede ser mayor que el mes final.',
            ], 422);
        }

        $startingBalance = array_key_exists('starting_balance', $validated)
            ? (float) $validated['starting_balance']
            : $sources['account_balance'];

        $paymentMonths = collect(config('projection.university_payment_months', []))
            ->map(fn ($m) => (int) $m)
            ->all();
        $paymentDay = (int) config('projection.payment_day', 15);

        $months      = [];
        $running     = $startingBalance;
        $totalRemain = 0.0;
        $totalFree   = 0.0;

        for ($month = $fromMonth; $month <= $toMonth; $month++) {
            $paysUniversity = in_array($month, $paymentMonths, true);
            $remaining      = $sources['monthly_remaining'];
            $freed          = $paysUniversity ? 0.0 : $sources['university_fee'];
            $delta          = $remaining + $freed;
            $running       += $delta;
            $totalRemain   += $remaining;
            $totalFree     += $freed;

            $months[] = [
                'year'             => $year,
                'month'            => $month,
                'label'            => self::MONTH_NAMES[$month],
                'pays_university'  => $paysUniversity,
                'kind'             => $paysUniversity ? 'pago' : 'libre',
                'kind_label'       => $paysUniversity
                    ? "Pago U ({$paymentDay})"
                    : 'Sin pago U',
                'monthly_remaining'=> round($remaining, 2),
                'university_freed' => round($freed, 2),
                'delta'            => round($delta, 2),
                'balance'          => round($running, 2),
            ];
        }

        return response()->json([
            'year'              => $year,
            'from_month'        => $fromMonth,
            'to_month'          => $toMonth,
            'payment_day'       => $paymentDay,
            'university_payment_months' => $paymentMonths,
            'settings'          => [
                'university_fee'              => $sources['university_fee'],
                'monthly_remaining'           => $sources['monthly_remaining'],
                'monthly_remaining_override'  => $settings->monthly_remaining,
                'uses_fixed_payments_remaining' => $settings->monthly_remaining === null,
            ],
            'sources'           => [
                'account_balance'          => $sources['account_balance'],
                'fixed_payments_remaining' => $sources['fixed_payments_remaining'],
            ],
            'starting_balance'  => round($startingBalance, 2),
            'months'            => $months,
            'summary'           => [
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

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'university_fee'    => ['required', 'numeric', 'min:0'],
            'monthly_remaining' => ['nullable', 'numeric'],
        ]);

        $settings                  = $this->settings($request);
        $settings->university_fee  = $validated['university_fee'];
        $settings->monthly_remaining = array_key_exists('monthly_remaining', $validated)
            ? $validated['monthly_remaining']
            : $settings->monthly_remaining;
        $settings->save();

        return response()->json([
            'university_fee'             => (float) $settings->university_fee,
            'monthly_remaining'          => $settings->monthly_remaining === null
                ? null
                : (float) $settings->monthly_remaining,
            'uses_fixed_payments_remaining' => $settings->monthly_remaining === null,
        ], 200);
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

    private function resolveSources(Request $request, ProjectionSetting $settings): array
    {
        $userId = $request->user()->id;

        $fixedSettings = FixedPaymentSetting::query()->firstOrCreate(
            ['user_id' => $userId],
            ['monthly_salary' => 0],
        );

        $expenses = (float) FixedPayment::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->sum('amount');

        $fixedRemaining = (float) $fixedSettings->monthly_salary - $expenses;

        $opening = (float) AccountingSetting::query()->firstOrCreate(
            ['user_id' => $userId],
            ['opening_balance_main' => 0],
        )->opening_balance_main;

        $global = DB::table((new Accounting)->getTable())
            ->where('user_id', $userId)
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'debe' THEN amount ELSE 0 END), 0) as total_debe")
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'haber' THEN amount ELSE 0 END), 0) as total_haber")
            ->first();

        $accountBalance = $opening
            + (float) ($global->total_haber ?? 0)
            - (float) ($global->total_debe ?? 0);

        $monthlyRemaining = $settings->monthly_remaining !== null
            ? (float) $settings->monthly_remaining
            : $fixedRemaining;

        return [
            'university_fee'           => (float) $settings->university_fee,
            'monthly_remaining'        => $monthlyRemaining,
            'fixed_payments_remaining' => $fixedRemaining,
            'account_balance'          => $accountBalance,
        ];
    }
}
