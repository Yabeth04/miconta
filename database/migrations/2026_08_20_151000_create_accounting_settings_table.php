<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('opening_balance_main', 14, 2)->default(0);
            $table->timestamps();
        });

        DB::table('accounting_settings')->insert([
            'opening_balance_main' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_settings');
    }
};
