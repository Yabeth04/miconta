<?php
namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $sysAdmin = Role::query()->updateOrCreate(
            ['name' => Role::SYS_ADMIN],
            ['label' => 'Administrador del sistema'],
        );

        Role::query()->updateOrCreate(
            ['name' => Role::USER],
            ['label' => 'Usuario'],
        );

        User::query()->updateOrCreate(
            ['username' => 'villaf14'],
            [
                'name'     => 'Yabeth David Villafuerte Sotelo',
                'email'    => 'soteloyabeth@gmail.com',
                'password' => 'admin1',
                'role_id'  => $sysAdmin->id,
            ],
        );

        $this->call(StudyPlanSeeder::class);
        $this->call(AccountingConceptSeeder::class);
        // $this->call(TrainingRoutineSeeder::class);
    }
}
