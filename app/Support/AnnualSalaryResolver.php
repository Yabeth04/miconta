<?php
namespace App\Support;

use App\Models\AnnualSalary;
use App\Models\FixedPaymentSetting;
use Carbon\Carbon;

class AnnualSalaryResolver
{
    /**
     * Resuelve el salario del año sin crear filas.
     * Solo se persiste al guardar explícitamente (Pagos fijos).
     */
    public static function forUserYear(int $userId, int $year): AnnualSalary
    {
        $existing = AnnualSalary::query()
            ->where('user_id', $userId)
            ->where('year', $year)
            ->first();

        if ($existing) {
            return $existing;
        }

        $fallback = AnnualSalary::query()
            ->where('user_id', $userId)
            ->where('year', '<', $year)
            ->orderByDesc('year')
            ->first();

        if ($fallback) {
            $payday = (float) $fallback->payday_amount;
        } else {
            $settings = FixedPaymentSetting::query()
                ->where('user_id', $userId)
                ->first();

            $payday = $settings
                ? ((float) $settings->monthly_salary) / 2
                : 0.0;
        }

        return new AnnualSalary([
            'user_id'       => $userId,
            'year'          => $year,
            'payday_amount' => round($payday, 2),
        ]);
    }

    public static function syncCurrentSettings(int $userId, float $paydayAmount, ?int $year = null): AnnualSalary
    {
        $year = $year ?? (int) Carbon::now()->year;

        $row = AnnualSalary::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'year'    => $year,
            ],
            [
                'payday_amount' => round($paydayAmount, 2),
            ],
        );

        if ($year === (int) Carbon::now()->year) {
            FixedPaymentSetting::query()->updateOrCreate(
                ['user_id' => $userId],
                ['monthly_salary' => round($paydayAmount * 2, 2)],
            );
        }

        return $row;
    }
}
