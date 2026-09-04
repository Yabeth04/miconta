<?php
namespace App\Http\Controllers;

use App\Models\LibraryExercise;
use App\Models\WorkoutDay;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSession;
use App\Models\WorkoutSessionExercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TrainingController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $this->ensureWeek($userId);
        $this->syncLibraryFromExisting($userId);

        $days = WorkoutDay::query()
            ->where('user_id', $userId)
            ->with('exercises')
            ->orderBy('weekday')
            ->get()
            ->map(fn (WorkoutDay $day) => $this->serializeDay($day));

        $sessions = WorkoutSession::query()
            ->where('user_id', $userId)
            ->with(['exercises', 'day'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(40)
            ->get()
            ->map(fn (WorkoutSession $session) => $this->serializeSession($session));

        $library = LibraryExercise::query()
            ->where('user_id', $userId)
            ->orderBy('muscle_group')
            ->orderBy('name')
            ->get()
            ->map(fn (LibraryExercise $item) => $this->serializeLibrary($item));

        $todayWeekday = (int) now()->isoWeekday();

        return response()->json([
            'today_weekday' => $todayWeekday,
            'days'          => $days,
            'sessions'      => $sessions,
            'library'       => $library,
            'summary'       => $this->summary($userId),
        ], 200);
    }

    public function updateDay(Request $request, WorkoutDay $workoutDay)
    {
        $this->authorizeDay($request, $workoutDay);

        $validated = $request->validate([
            'focus'   => ['nullable', 'string', 'max:255'],
            'is_rest' => ['nullable', 'boolean'],
            'notes'   => ['nullable', 'string', 'max:1000'],
        ]);

        $workoutDay->fill([
            'focus'   => array_key_exists('focus', $validated)
                ? $this->nullableString($validated['focus'] ?? null)
                : $workoutDay->focus,
            'is_rest' => array_key_exists('is_rest', $validated)
                ? (bool) $validated['is_rest']
                : $workoutDay->is_rest,
            'notes'   => array_key_exists('notes', $validated)
                ? $this->nullableString($validated['notes'] ?? null)
                : $workoutDay->notes,
        ]);
        $workoutDay->save();

        return response()->json($this->serializeDay($workoutDay->load('exercises')), 200);
    }

    public function storeExercise(Request $request, WorkoutDay $workoutDay)
    {
        $this->authorizeDay($request, $workoutDay);
        $validated = $this->validateExercise($request);
        $library = $this->upsertLibrary((int) $request->user()->id, $validated);

        $maxOrder = (int) $workoutDay->exercises()->max('sort_order');

        $exercise = $workoutDay->exercises()->create([
            ...$validated,
            'library_exercise_id' => $library->id,
            'sort_order'          => $maxOrder + 1,
        ]);

        $this->rebuildFocusFromExercises($workoutDay->fresh('exercises'));

        return response()->json($this->serializeExercise($exercise), 201);
    }

    public function attachLibraryExercise(Request $request, WorkoutDay $workoutDay)
    {
        $this->authorizeDay($request, $workoutDay);

        $validated = $request->validate([
            'library_exercise_id' => ['required', 'integer', 'exists:exercise_library,id'],
            'sets'                => ['nullable', 'integer', 'min:1', 'max:30'],
            'reps'                => ['nullable', 'integer', 'min:1', 'max:100'],
            'load_type'           => ['nullable', Rule::in(WorkoutExercise::LOAD_TYPES)],
            'load_value'          => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'notes'               => ['nullable', 'string', 'max:255'],
        ]);

        $library = LibraryExercise::query()->findOrFail($validated['library_exercise_id']);
        abort_unless((int) $library->user_id === (int) $request->user()->id, 404);

        $maxOrder = (int) $workoutDay->exercises()->max('sort_order');

        $exercise = $workoutDay->exercises()->create([
            'library_exercise_id' => $library->id,
            'name'                => $library->name,
            'muscle_group'        => $library->muscle_group,
            'sets'                => $validated['sets'] ?? 4,
            'reps'                => $validated['reps'] ?? 11,
            'load_type'           => $validated['load_type'] ?? 'level',
            'load_value'          => array_key_exists('load_value', $validated)
                ? ($validated['load_value'] !== null ? round((float) $validated['load_value'], 2) : null)
                : null,
            'notes'               => array_key_exists('notes', $validated)
                ? $this->nullableString($validated['notes'] ?? null)
                : null,
            'sort_order'          => $maxOrder + 1,
        ]);

        $this->rebuildFocusFromExercises($workoutDay->fresh('exercises'));

        return response()->json($this->serializeExercise($exercise), 201);
    }

    public function storeLibraryExercise(Request $request)
    {
        $validated = $this->validateLibraryExercise($request);
        $library = $this->upsertLibrary((int) $request->user()->id, $validated);

        return response()->json($this->serializeLibrary($library->fresh()), 201);
    }

    public function updateLibraryExercise(Request $request, LibraryExercise $libraryExercise)
    {
        $this->authorizeLibrary($request, $libraryExercise);
        $validated = $this->validateLibraryExercise($request);

        // Si cambian el nombre, respetar unique por usuario
        $exists = LibraryExercise::query()
            ->where('user_id', $request->user()->id)
            ->where('name', $validated['name'])
            ->where('id', '!=', $libraryExercise->id)
            ->exists();

        abort_if($exists, 422, 'Ya tenés un ejercicio con ese nombre en la biblioteca.');

        $libraryExercise->update($validated);

        return response()->json($this->serializeLibrary($libraryExercise->fresh()), 200);
    }

    public function destroyLibraryExercise(Request $request, LibraryExercise $libraryExercise)
    {
        $this->authorizeLibrary($request, $libraryExercise);

        // Las instancias en días quedan (FK → null); solo se saca del catálogo.
        WorkoutExercise::query()
            ->where('library_exercise_id', $libraryExercise->id)
            ->update(['library_exercise_id' => null]);

        $libraryExercise->delete();

        return response()->json(['message' => 'Ejercicio eliminado de la biblioteca.'], 200);
    }

    public function updateExercise(Request $request, WorkoutExercise $workoutExercise)
    {
        $this->authorizeExercise($request, $workoutExercise);
        $validated = $this->validateExercise($request);
        $workoutExercise->update($validated);

        $day = $workoutExercise->day()->with('exercises')->first();
        if ($day) {
            $this->rebuildFocusFromExercises($day);
        }

        return response()->json($this->serializeExercise($workoutExercise->fresh()), 200);
    }

    public function destroyExercise(Request $request, WorkoutExercise $workoutExercise)
    {
        $this->authorizeExercise($request, $workoutExercise);
        $day = $workoutExercise->day;
        $workoutExercise->delete();

        if ($day) {
            $this->rebuildFocusFromExercises($day->fresh('exercises'));
        }

        return response()->json(['message' => 'Ejercicio eliminado.'], 200);
    }

    public function reorderExercises(Request $request, WorkoutDay $workoutDay)
    {
        $this->authorizeDay($request, $workoutDay);

        $validated = $request->validate([
            'exercise_ids'   => ['required', 'array', 'min:1'],
            'exercise_ids.*' => ['integer'],
        ]);

        $orderedIds = array_values(array_map('intval', $validated['exercise_ids']));
        $ownedIds   = $workoutDay->exercises()->pluck('id')->map(fn($id) => (int) $id)->all();

        sort($ownedIds);
        $sortedIncoming = $orderedIds;
        sort($sortedIncoming);

        abort_unless($sortedIncoming === $ownedIds, 422, 'El orden de ejercicios no es válido.');

        DB::transaction(function () use ($workoutDay, $orderedIds) {
            foreach ($orderedIds as $index => $exerciseId) {
                WorkoutExercise::query()
                    ->where('workout_day_id', $workoutDay->id)
                    ->where('id', $exerciseId)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json($this->serializeDay($workoutDay->fresh('exercises')), 200);
    }

    /**
     * Reordena los grupos musculares de un día (y sus ejercicios).
     */
    public function reorderGroups(Request $request, WorkoutDay $workoutDay)
    {
        $this->authorizeDay($request, $workoutDay);

        $validated = $request->validate([
            'groups'   => ['required', 'array', 'min:1'],
            'groups.*' => ['nullable', 'string', 'max:80'],
        ]);

        DB::transaction(function () use ($workoutDay, $validated) {
            $order = 1;

            foreach ($validated['groups'] as $rawGroup) {
                $muscle = $this->nullableString(is_string($rawGroup) ? $rawGroup : null);

                $query = WorkoutExercise::query()
                    ->where('workout_day_id', $workoutDay->id)
                    ->orderBy('sort_order')
                    ->orderBy('id');

                if ($muscle === null) {
                    $query->where(function ($q) {
                        $q->whereNull('muscle_group')->orWhere('muscle_group', '');
                    });
                } else {
                    $query->where('muscle_group', $muscle);
                }

                foreach ($query->get() as $exercise) {
                    $exercise->update(['sort_order' => $order++]);
                }
            }

            $this->rebuildFocusFromExercises($workoutDay->fresh('exercises'));
        });

        return response()->json($this->serializeDay($workoutDay->fresh('exercises')), 200);
    }

    /**
     * Mueve todos los ejercicios de un grupo muscular de un día a otro.
     */
    public function moveGroup(Request $request)
    {
        $validated = $request->validate([
            'source_day_id' => ['required', 'integer', 'exists:workout_days,id'],
            'target_day_id' => ['required', 'integer', 'exists:workout_days,id'],
            'muscle_group'  => ['nullable', 'string', 'max:80'],
        ]);

        $userId = (int) $request->user()->id;
        $source = WorkoutDay::query()->with('exercises')->findOrFail($validated['source_day_id']);
        $target = WorkoutDay::query()->with('exercises')->findOrFail($validated['target_day_id']);

        abort_unless((int) $source->user_id === $userId, 404);
        abort_unless((int) $target->user_id === $userId, 404);

        if ((int) $source->id === (int) $target->id) {
            return response()->json([
                'source' => $this->serializeDay($source),
                'target' => $this->serializeDay($target),
            ], 200);
        }

        $muscle = $this->nullableString($validated['muscle_group'] ?? null);

        DB::transaction(function () use ($source, $target, $muscle) {
            $query = WorkoutExercise::query()
                ->where('workout_day_id', $source->id)
                ->orderBy('sort_order')
                ->orderBy('id');

            if ($muscle === null) {
                $query->where(function ($q) {
                    $q->whereNull('muscle_group')->orWhere('muscle_group', '');
                });
            } else {
                $query->where('muscle_group', $muscle);
            }

            $exercises = $query->get();
            abort_if($exercises->isEmpty(), 422, 'No hay ejercicios en ese grupo.');

            $maxOrder = (int) $target->exercises()->max('sort_order');

            foreach ($exercises->values() as $index => $exercise) {
                $exercise->update([
                    'workout_day_id' => $target->id,
                    'sort_order'     => $maxOrder + $index + 1,
                ]);
            }

            if ($target->is_rest) {
                $target->update(['is_rest' => false]);
            }

            $this->rebuildFocusFromExercises($source->fresh('exercises'));
            $this->rebuildFocusFromExercises($target->fresh('exercises'));
        });

        return response()->json([
            'source' => $this->serializeDay($source->fresh('exercises')),
            'target' => $this->serializeDay($target->fresh('exercises')),
        ], 200);
    }

    /**
     * Copiar o intercambiar la rutina de un día hacia otro (plan permanente).
     */
    public function reassignDay(Request $request, WorkoutDay $workoutDay)
    {
        $this->authorizeDay($request, $workoutDay);

        $validated = $request->validate([
            'source_day_id' => ['required', 'integer', 'exists:workout_days,id'],
            'mode'          => ['required', Rule::in(['copy', 'swap'])],
        ]);

        $source = WorkoutDay::query()
            ->with('exercises')
            ->findOrFail($validated['source_day_id']);

        abort_unless((int) $source->user_id === (int) $request->user()->id, 404);

        if ((int) $source->id === (int) $workoutDay->id) {
            return response()->json($this->serializeDay($workoutDay->load('exercises')), 200);
        }

        $workoutDay->load('exercises');

        DB::transaction(function () use ($workoutDay, $source, $validated) {
            if ($validated['mode'] === 'swap') {
                $targetSnap = $this->daySnapshot($workoutDay);
                $sourceSnap = $this->daySnapshot($source);
                $this->applyDaySnapshot($workoutDay, $sourceSnap);
                $this->applyDaySnapshot($source, $targetSnap);
            } else {
                $this->applyDaySnapshot($workoutDay, $this->daySnapshot($source));
            }
        });

        return response()->json([
            'target' => $this->serializeDay($workoutDay->fresh('exercises')),
            'source' => $this->serializeDay($source->fresh('exercises')),
        ], 200);
    }

    public function storeSession(Request $request)
    {
        $validated = $this->validateSession($request);
        $userId    = $request->user()->id;

        if (! empty($validated['workout_day_id'])) {
            $day = WorkoutDay::query()->findOrFail($validated['workout_day_id']);
            abort_unless((int) $day->user_id === (int) $userId, 404);
        }

        $session = DB::transaction(function () use ($validated, $userId) {
            $session = WorkoutSession::query()->create([
                'user_id'          => $userId,
                'workout_day_id'   => $validated['workout_day_id'] ?? null,
                'date'             => $validated['date'],
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'calories'         => $validated['calories'] ?? null,
                'notes'            => $this->nullableString($validated['notes'] ?? null),
            ]);

            $this->syncSessionExercises($session, $validated['exercises'] ?? []);

            return $session->load(['exercises', 'day']);
        });

        return response()->json($this->serializeSession($session), 201);
    }

    public function updateSession(Request $request, WorkoutSession $workoutSession)
    {
        $this->authorizeSession($request, $workoutSession);
        $validated = $this->validateSession($request);

        if (! empty($validated['workout_day_id'])) {
            $day = WorkoutDay::query()->findOrFail($validated['workout_day_id']);
            abort_unless((int) $day->user_id === (int) $request->user()->id, 404);
        }

        $session = DB::transaction(function () use ($validated, $workoutSession) {
            $workoutSession->update([
                'workout_day_id'   => $validated['workout_day_id'] ?? null,
                'date'             => $validated['date'],
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'calories'         => $validated['calories'] ?? null,
                'notes'            => $this->nullableString($validated['notes'] ?? null),
            ]);

            $workoutSession->exercises()->delete();
            $this->syncSessionExercises($workoutSession, $validated['exercises'] ?? []);

            return $workoutSession->load(['exercises', 'day']);
        });

        return response()->json($this->serializeSession($session), 200);
    }

    public function destroySession(Request $request, WorkoutSession $workoutSession)
    {
        $this->authorizeSession($request, $workoutSession);
        $workoutSession->delete();

        return response()->json(['message' => 'Sesión eliminada.'], 200);
    }

    private function ensureWeek(int $userId): void
    {
        for ($weekday = 1; $weekday <= 7; $weekday++) {
            WorkoutDay::query()->firstOrCreate(
                ['user_id' => $userId, 'weekday' => $weekday],
                ['focus' => null, 'is_rest' => false, 'notes' => null],
            );
        }
    }

    private function syncLibraryFromExisting(int $userId): void
    {
        $unlinked = WorkoutExercise::query()
            ->whereHas('day', fn ($q) => $q->where('user_id', $userId))
            ->whereNull('library_exercise_id')
            ->orderBy('id')
            ->get();

        if ($unlinked->isEmpty()) {
            return;
        }

        $byName = LibraryExercise::query()
            ->where('user_id', $userId)
            ->get()
            ->keyBy(fn (LibraryExercise $item) => mb_strtolower($item->name));

        // Solo si la biblioteca está vacía, sembramos desde la rutina (migración inicial).
        // Si el usuario borró un ítem del catálogo, no lo recreamos aunque siga en un día.
        $seedMissing = $byName->isEmpty();

        foreach ($unlinked as $exercise) {
            $key = mb_strtolower((string) $exercise->name);
            $library = $byName->get($key);

            if (! $library && $seedMissing) {
                $library = LibraryExercise::query()->create([
                    'user_id'      => $userId,
                    'name'         => $exercise->name,
                    'muscle_group' => $exercise->muscle_group,
                ]);
                $byName->put($key, $library);
            }

            if ($library) {
                $exercise->update(['library_exercise_id' => $library->id]);
            }
        }
    }

    /**
     * @param  array{name: string, muscle_group: ?string}  $data
     */
    private function upsertLibrary(int $userId, array $data): LibraryExercise
    {
        return LibraryExercise::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'name'    => $data['name'],
            ],
            [
                'muscle_group' => $data['muscle_group'] ?? null,
            ],
        );
    }

    private function summary(int $userId): array
    {
        $from = now()->startOfWeek();

        $weekSessions = WorkoutSession::query()
            ->where('user_id', $userId)
            ->whereDate('date', '>=', $from->toDateString())
            ->with('exercises')
            ->get();

        return [
            'week_sessions' => $weekSessions->count(),
            'week_minutes'  => (int) $weekSessions->sum('duration_minutes'),
            'week_calories' => (int) $weekSessions->sum('calories'),
        ];
    }

    /**
     * @return array{name: string, muscle_group: ?string}
     */
    private function validateLibraryExercise(Request $request): array
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'muscle_group' => ['nullable', 'string', 'max:80'],
        ]);

        $validated['name']         = trim($validated['name']);
        $validated['muscle_group'] = $this->nullableString($validated['muscle_group'] ?? null);

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateExercise(Request $request): array
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'muscle_group' => ['nullable', 'string', 'max:80'],
            'sets'         => ['required', 'integer', 'min:1', 'max:30'],
            'reps'         => ['required', 'integer', 'min:1', 'max:100'],
            'load_type'    => ['required', Rule::in(WorkoutExercise::LOAD_TYPES)],
            'load_value'   => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'notes'        => ['nullable', 'string', 'max:255'],
        ]);

        $validated['name']         = trim($validated['name']);
        $validated['muscle_group'] = $this->nullableString($validated['muscle_group'] ?? null);
        $validated['notes']        = $this->nullableString($validated['notes'] ?? null);
        $validated['load_value']   = $validated['load_value'] === null
            ? null
            : round((float) $validated['load_value'], 2);

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSession(Request $request): array
    {
        return $request->validate([
            'date'                     => ['required', 'date'],
            'workout_day_id'           => ['nullable', 'integer'],
            'duration_minutes'         => ['nullable', 'integer', 'min:1', 'max:600'],
            'calories'                 => ['nullable', 'integer', 'min:0', 'max:5000'],
            'notes'                    => ['nullable', 'string', 'max:1000'],
            'exercises'                => ['nullable', 'array'],
            'exercises.*.name'         => ['required', 'string', 'max:255'],
            'exercises.*.muscle_group' => ['nullable', 'string', 'max:80'],
            'exercises.*.sets'         => ['required', 'integer', 'min:0', 'max:30'],
            'exercises.*.reps'         => ['required', 'integer', 'min:0', 'max:100'],
            'exercises.*.load_type'    => ['required', Rule::in(WorkoutExercise::LOAD_TYPES)],
            'exercises.*.load_value'   => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'exercises.*.notes'        => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function rebuildFocusFromExercises(WorkoutDay $day): void
    {
        if ($day->is_rest) {
            return;
        }

        $groups = $day->exercises
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->pluck('muscle_group')
            ->map(fn ($value) => $this->nullableString($value))
            ->filter()
            ->unique()
            ->values();

        $day->update([
            'focus' => $groups->isEmpty() ? $day->focus : $groups->implode(' + '),
        ]);
    }

    /**
     * @return array{focus: ?string, is_rest: bool, notes: ?string, exercises: list<array<string, mixed>>}
     */
    private function daySnapshot(WorkoutDay $day): array
    {
        return [
            'focus'     => $day->focus,
            'is_rest'   => (bool) $day->is_rest,
            'notes'     => $day->notes,
            'exercises' => $day->exercises->map(fn(WorkoutExercise $item) => [
                'name'         => $item->name,
                'muscle_group' => $item->muscle_group,
                'sets'         => (int) $item->sets,
                'reps'         => (int) $item->reps,
                'load_type'    => $item->load_type,
                'load_value'   => $item->load_value,
                'notes'        => $item->notes,
                'sort_order'   => (int) $item->sort_order,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array{focus: ?string, is_rest: bool, notes: ?string, exercises: list<array<string, mixed>>}  $snapshot
     */
    private function applyDaySnapshot(WorkoutDay $day, array $snapshot): void
    {
        $day->update([
            'focus'   => $snapshot['focus'],
            'is_rest' => (bool) $snapshot['is_rest'],
            'notes'   => $snapshot['notes'],
        ]);

        $day->exercises()->delete();

        foreach (array_values($snapshot['exercises']) as $index => $item) {
            $day->exercises()->create([
                'name'         => $item['name'],
                'muscle_group' => $item['muscle_group'] ?? null,
                'sets'         => (int) $item['sets'],
                'reps'         => (int) $item['reps'],
                'load_type'    => $item['load_type'],
                'load_value'   => $item['load_value'] ?? null,
                'notes'        => $item['notes'] ?? null,
                'sort_order'   => $item['sort_order'] ?? $index,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $exercises
     */
    private function syncSessionExercises(WorkoutSession $session, array $exercises): void
    {
        foreach (array_values($exercises) as $index => $item) {
            WorkoutSessionExercise::query()->create([
                'workout_session_id' => $session->id,
                'name'               => trim((string) $item['name']),
                'muscle_group'       => $this->nullableString($item['muscle_group'] ?? null),
                'sets'               => (int) $item['sets'],
                'reps'               => (int) $item['reps'],
                'load_type'          => $item['load_type'],
                'load_value'         => isset($item['load_value']) && $item['load_value'] !== null
                    ? round((float) $item['load_value'], 2)
                    : null,
                'notes'              => $this->nullableString($item['notes'] ?? null),
                'sort_order'         => $index,
            ]);
        }
    }

    private function serializeDay(WorkoutDay $day): array
    {
        return [
            'id'        => $day->id,
            'weekday'   => (int) $day->weekday,
            'label'     => $day->weekdayLabel(),
            'focus'     => $day->focus,
            'is_rest'   => (bool) $day->is_rest,
            'notes'     => $day->notes,
            'exercises' => $day->exercises->map(fn(WorkoutExercise $item) => $this->serializeExercise($item))->values(),
        ];
    }

    private function serializeExercise(WorkoutExercise $exercise): array
    {
        return [
            'id'                  => $exercise->id,
            'library_exercise_id' => $exercise->library_exercise_id,
            'name'                => $exercise->name,
            'muscle_group'        => $exercise->muscle_group,
            'sets'                => (int) $exercise->sets,
            'reps'                => (int) $exercise->reps,
            'load_type'           => $exercise->load_type,
            'load_value'          => $exercise->load_value !== null ? (float) $exercise->load_value : null,
            'notes'               => $exercise->notes,
            'sort_order'          => (int) $exercise->sort_order,
        ];
    }

    private function serializeLibrary(LibraryExercise $exercise): array
    {
        return [
            'id'           => $exercise->id,
            'name'         => $exercise->name,
            'muscle_group' => $exercise->muscle_group,
        ];
    }

    private function serializeSession(WorkoutSession $session): array
    {
        return [
            'id'               => $session->id,
            'workout_day_id'   => $session->workout_day_id,
            'weekday'          => $session->day?->weekday,
            'weekday_label'    => $session->day?->weekdayLabel(),
            'focus'            => $session->day?->focus,
            'date'             => $session->date?->toDateString(),
            'duration_minutes' => $session->duration_minutes,
            'calories'         => $session->calories,
            'notes'            => $session->notes,
            'exercises'        => $session->exercises->map(fn(WorkoutSessionExercise $item) => [
                'id'           => $item->id,
                'name'         => $item->name,
                'muscle_group' => $item->muscle_group,
                'sets'         => (int) $item->sets,
                'reps'         => (int) $item->reps,
                'load_type'    => $item->load_type,
                'load_value'   => $item->load_value !== null ? (float) $item->load_value : null,
                'notes'        => $item->notes,
            ])->values(),
        ];
    }

    private function authorizeDay(Request $request, WorkoutDay $day): void
    {
        abort_unless((int) $day->user_id === (int) $request->user()->id, 404);
    }

    private function authorizeLibrary(Request $request, LibraryExercise $exercise): void
    {
        abort_unless((int) $exercise->user_id === (int) $request->user()->id, 404);
    }

    private function authorizeExercise(Request $request, WorkoutExercise $exercise): void
    {
        $exercise->loadMissing('day');
        abort_unless((int) $exercise->day?->user_id === (int) $request->user()->id, 404);
    }

    private function authorizeSession(Request $request, WorkoutSession $session): void
    {
        abort_unless((int) $session->user_id === (int) $request->user()->id, 404);
    }

    private function nullableString(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
