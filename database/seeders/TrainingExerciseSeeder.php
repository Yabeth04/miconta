<?php
namespace Database\Seeders;

use App\Models\TrainingExercise;
use App\Models\User;
use Illuminate\Database\Seeder;

class TrainingExerciseSeeder extends Seeder
{
    /** @var list<array{name: string, muscle_group: string}> */
    private const EXERCISES = [
        ['name' => 'Press de pecho', 'muscle_group' => 'pecho'],
        ['name' => 'Butterfly (cerrar y abrir)', 'muscle_group' => 'pecho'],
        ['name' => 'Jalón en polea alta', 'muscle_group' => 'espalda'],
        ['name' => 'Remo en polea baja', 'muscle_group' => 'espalda'],
        ['name' => 'Pullover en polea', 'muscle_group' => 'espalda'],
        ['name' => 'Elevación lateral con polea', 'muscle_group' => 'hombros'],
        ['name' => 'Elevación frontal con polea baja', 'muscle_group' => 'hombros'],
        ['name' => 'Extensión hacia abajo', 'muscle_group' => 'triceps'],
        ['name' => 'Extensión de tríceps por encima de la cabeza con polea', 'muscle_group' => 'triceps'],
        ['name' => 'Curl Scott', 'muscle_group' => 'biceps'],
        ['name' => 'Curl de bíceps en polea baja', 'muscle_group' => 'biceps'],
        ['name' => 'Extensión de piernas', 'muscle_group' => 'piernas'],
        ['name' => 'Leg Curl de pie con polea baja + tobillera', 'muscle_group' => 'piernas'],
        ['name' => 'Glute Kickback con polea baja + tobillera', 'muscle_group' => 'piernas'],
        ['name' => 'Crunch con polea alta', 'muscle_group' => 'core'],
    ];

    public function run(): void
    {
        $keep = array_column(self::EXERCISES, 'name');

        User::query()->each(function (User $user) use ($keep) {
            TrainingExercise::query()
                ->where('user_id', $user->id)
                ->whereNotIn('name', $keep)
                ->whereDoesntHave('sessionExercises')
                ->delete();

            foreach (self::EXERCISES as $row) {
                TrainingExercise::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'name'    => $row['name'],
                    ],
                    [
                        'muscle_group' => $row['muscle_group'],
                        'load_type'    => 'level',
                    ],
                );
            }
        });
    }
}
