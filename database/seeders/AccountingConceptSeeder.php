<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingConceptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $concepts = [
            'Gasolina',
            'Pago alquiler casa',
            'Pago extra a mi mamá',
            'Pago de salario oficinaonlinecr',
            'ahorro a MM',
        ];

        foreach ($concepts as $concept) {
            DB::table('accounting_concepts')->insert([
                'name'       => $concept,
                'user_id'    => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
