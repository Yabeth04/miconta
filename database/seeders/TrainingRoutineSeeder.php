<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkoutDay;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSession;
use App\Models\WorkoutSessionExercise;
use Illuminate\Database\Seeder;

/**
 * Rutina de ejemplo (la del usuario) para ver la UI ya armada.
 * Idempotente: limpia días/ejercicios/sesiones de entrenamiento del usuario y vuelve a cargar.
 */
class TrainingRoutineSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('username', 'villaf14')->first() ?? User::query()->orderBy('id')->first();

        if (! $user) {
            $this->command?->warn('No hay usuario para seed de entrenamiento.');

            return;
        }

        WorkoutSessionExercise::query()
            ->whereHas('session', fn($q) => $q->where('user_id', $user->id))
            ->delete();
        WorkoutSession::query()->where('user_id', $user->id)->delete();
        WorkoutExercise::query()
            ->whereHas('day', fn($q) => $q->where('user_id', $user->id))
            ->delete();
        WorkoutDay::query()->where('user_id', $user->id)->delete();

        $plan = [
            1 => [
                'focus'     => 'Pecho + hombros + tríceps',
                'is_rest'   => false,
                'exercises' => [
                    ['name' => 'Press de pecho', 'muscle_group' => 'Pecho', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 5, 'notes' => null],
                    ['name' => 'Butterfly (cerrar y abrir)', 'muscle_group' => 'Pecho', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 4, 'notes' => null],
                    ['name' => 'Elevación lateral con polea', 'muscle_group' => 'Hombros', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 2, 'notes' => null],
                    ['name' => 'Elevación frontal con polea baja', 'muscle_group' => 'Hombros', 'reps' => 11, 'sets' => 3, 'load_type' => 'level', 'load_value' => 2, 'notes' => null],
                    ['name' => 'Extensión hacia abajo', 'muscle_group' => 'Tríceps', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 3, 'notes' => null],
                    ['name' => 'Extensión de tríceps por encima de la cabeza', 'muscle_group' => 'Tríceps', 'reps' => 11, 'sets' => 3, 'load_type' => 'level', 'load_value' => 2, 'notes' => null],
                ],
            ],
            2 => [
                'focus'     => 'Piernas + abdomen',
                'is_rest'   => false,
                'exercises' => [
                    ['name' => 'Extensión de piernas', 'muscle_group' => 'Piernas', 'reps' => 12, 'sets' => 4, 'load_type' => 'level', 'load_value' => 4, 'notes' => null],
                    ['name' => 'Leg Curl de pie con polea baja + tobillera', 'muscle_group' => 'Piernas', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 3, 'notes' => null],
                    ['name' => 'Glute Kickback con polea baja + tobillera', 'muscle_group' => 'Piernas', 'reps' => 11, 'sets' => 3, 'load_type' => 'level', 'load_value' => 3, 'notes' => null],
                    ['name' => 'Crunch con polea alta', 'muscle_group' => 'Abdomen', 'reps' => 15, 'sets' => 3, 'load_type' => 'level', 'load_value' => 2, 'notes' => null],
                ],
            ],
            3 => [
                'focus'     => 'Espalda + bíceps + antebrazo',
                'is_rest'   => false,
                'exercises' => [
                    ['name' => 'Jalón en polea alta', 'muscle_group' => 'Espalda', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 6, 'notes' => 'Jalando de arriba hacia abajo'],
                    ['name' => 'Remo en polea baja', 'muscle_group' => 'Espalda', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 5, 'notes' => 'En el piso jalando'],
                    ['name' => 'Pullover en polea', 'muscle_group' => 'Espalda', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 2, 'notes' => 'Remo de abajo hacia el pecho'],
                    ['name' => 'Curl Scott', 'muscle_group' => 'Bíceps', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 2, 'notes' => 'Apoyando'],
                    ['name' => 'Curl de bíceps en polea baja', 'muscle_group' => 'Bíceps', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 2, 'notes' => null],
                    ['name' => 'Polea baja Antebrazo', 'muscle_group' => 'Antebrazo', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 1, 'notes' => null],
                ],
            ],
            4 => [
                'focus'     => 'Correr',
                'is_rest'   => true,
                'exercises' => [
                    ['name' => 'Correr', 'muscle_group' => 'Cardio', 'reps' => 1, 'sets' => 1, 'load_type' => 'km', 'load_value' => 5, 'notes' => null],
                ],
            ],
            5 => [
                'focus'     => 'Piernas + hombros + bíceps + tríceps',
                'is_rest'   => false,
                'exercises' => [
                    ['name' => 'Extensión de piernas', 'muscle_group' => 'Piernas', 'reps' => 12, 'sets' => 3, 'load_type' => 'level', 'load_value' => 4, 'notes' => null],
                    ['name' => 'Leg Curl de pie con polea baja + tobillera', 'muscle_group' => 'Piernas', 'reps' => 11, 'sets' => 3, 'load_type' => 'level', 'load_value' => 3, 'notes' => null],
                    ['name' => 'Elevación lateral con polea', 'muscle_group' => 'Hombros', 'reps' => 11, 'sets' => 3, 'load_type' => 'level', 'load_value' => 2, 'notes' => null],
                    ['name' => 'Curl de bíceps en polea baja', 'muscle_group' => 'Bíceps', 'reps' => 11, 'sets' => 3, 'load_type' => 'level', 'load_value' => 2, 'notes' => null],
                    ['name' => 'Extensión hacia abajo', 'muscle_group' => 'Tríceps', 'reps' => 11, 'sets' => 3, 'load_type' => 'level', 'load_value' => 3, 'notes' => null],
                ],
            ],
            6 => [
                'focus'     => 'Pecho + espalda + abdomen',
                'is_rest'   => false,
                'exercises' => [
                    ['name' => 'Press de pecho', 'muscle_group' => 'Pecho', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 5, 'notes' => null],
                    ['name' => 'Butterfly (cerrar y abrir)', 'muscle_group' => 'Pecho', 'reps' => 11, 'sets' => 3, 'load_type' => 'level', 'load_value' => 4, 'notes' => null],
                    ['name' => 'Jalón en polea alta', 'muscle_group' => 'Espalda', 'reps' => 11, 'sets' => 4, 'load_type' => 'level', 'load_value' => 5, 'notes' => null],
                    ['name' => 'Remo en polea baja', 'muscle_group' => 'Espalda', 'reps' => 11, 'sets' => 3, 'load_type' => 'level', 'load_value' => 4, 'notes' => null],
                    ['name' => 'Crunch con polea alta', 'muscle_group' => 'Abdomen', 'reps' => 15, 'sets' => 3, 'load_type' => 'level', 'load_value' => 2, 'notes' => null],
                ],
            ],
            7 => [
                'focus'     => 'Correr',
                'is_rest'   => true,
                'exercises' => [
                    ['name' => 'Correr', 'muscle_group' => 'Cardio', 'reps' => 1, 'sets' => 1, 'load_type' => 'km', 'load_value' => 5, 'notes' => null],
                ],
            ],
        ];

        $daysByWeekday = [];

        foreach ($plan as $weekday => $dayData) {
            $day = WorkoutDay::query()->create([
                'user_id' => $user->id,
                'weekday' => $weekday,
                'focus'   => $dayData['focus'],
                'is_rest' => $dayData['is_rest'],
            ]);

            foreach ($dayData['exercises'] as $index => $exercise) {
                WorkoutExercise::query()->create([
                     ...$exercise,
                    'workout_day_id' => $day->id,
                    'sort_order'     => $index + 1,
                ]);
            }

            $daysByWeekday[$weekday] = $day->fresh('exercises');
        }

        // Una sesión de ejemplo (miércoles anterior) para ver Historial
        $wed     = $daysByWeekday[3];
        $session = WorkoutSession::query()->create([
            'user_id'          => $user->id,
            'workout_day_id'   => $wed->id,
            'date'             => now()->subDays(7)->toDateString(),
            'duration_minutes' => 48,
            'calories'         => 220,
            'notes'            => 'Sesión de ejemplo para ver el historial',
        ]);

        foreach ($wed->exercises as $index => $exercise) {
            WorkoutSessionExercise::query()->create([
                'workout_session_id' => $session->id,
                'name'               => $exercise->name,
                'muscle_group'       => $exercise->muscle_group,
                'sets'               => $exercise->sets,
                'reps'               => $exercise->reps,
                'load_type'          => $exercise->load_type,
                'load_value'         => $exercise->load_value,
                'notes'              => $exercise->notes,
                'sort_order'         => $index + 1,
            ]);
        }

        $this->command?->info("Rutina de ejemplo cargada para {$user->username}.");
    }
}
