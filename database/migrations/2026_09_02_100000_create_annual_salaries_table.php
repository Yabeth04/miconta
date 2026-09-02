<?php

use App\Models\FixedPaymentSetting;
use App\Models\AnnualSalary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('payday_amount', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'year']);
        });

        $year = (int) now()->year;

        FixedPaymentSetting::query()->each(function (FixedPaymentSetting $settings) use ($year) {
            $monthly = (float) $settings->monthly_salary;
            if ($monthly <= 0) {
                return;
            }

            AnnualSalary::query()->firstOrCreate(
                [
                    'user_id' => $settings->user_id,
                    'year'    => $year,
                ],
                [
                    'payday_amount' => round($monthly / 2, 2),
                ],
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_salaries');
    }
};
