<?php
namespace App\Http\Controllers;

use App\Models\StudySubject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudyPlanController extends Controller
{
    public function index()
    {
        $termsConfig = config('study_plan.terms');
        $electives = config('study_plan.electives');

        $subjects = StudySubject::query()
            ->orderBy('term_number')
            ->orderBy('id')
            ->get()
            ->groupBy('term_number');

        $terms = collect($termsConfig)->map(function (array $term, int $number) use ($subjects, $electives) {
            $termSubjects = ($subjects->get($number) ?? collect())->map(function (StudySubject $subject) use ($electives) {
                $options = [];

                if ($subject->is_elective_slot && $subject->elective_group) {
                    $prefs = $subject->elective_preferences ?? [];
                    $options = collect($electives[$subject->elective_group] ?? [])
                        ->map(fn (array $opt) => [
                            'key'             => $opt['key'],
                            'name'            => $opt['name'],
                            'preference_note' => $prefs[$opt['key']] ?? null,
                        ])
                        ->values();
                }

                return [
                    'id'                    => $subject->id,
                    'term_number'           => $subject->term_number,
                    'name'                  => $subject->name,
                    'is_elective_slot'      => $subject->is_elective_slot,
                    'elective_group'        => $subject->elective_group,
                    'status'                => $subject->status,
                    'note'                  => $subject->note,
                    'selected_elective_key' => $subject->selected_elective_key,
                    'elective_options'      => $options,
                ];
            })->values();

            return [
                'number'   => $number,
                'name'     => $term['name'],
                'color'    => $term['color'],
                'subjects' => $termSubjects,
            ];
        })->values();

        return response()->json([
            'terms'   => $terms,
            'summary' => [
                'total'     => StudySubject::query()->count(),
                'aprobadas' => StudySubject::query()->where('status', 'aprobado')->count(),
            ],
        ]);
    }

    public function storeSubject(Request $request)
    {
        $validated = $request->validate([
            'term_number' => ['required', 'integer', Rule::in(array_keys(config('study_plan.terms')))],
            'name'        => ['required', 'string', 'max:255'],
        ]);

        $subject = StudySubject::query()->create([
            'term_number' => $validated['term_number'],
            'name'        => $validated['name'],
        ]);

        return response()->json($subject, 201);
    }

    public function updateSubject(Request $request, StudySubject $subject)
    {
        $validated = $request->validate([
            'term_number' => ['sometimes', 'integer', Rule::in(array_keys(config('study_plan.terms')))],
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
        ]);

        $subject->update($validated);

        return response()->json($subject->fresh());
    }

    public function destroySubject(StudySubject $subject)
    {
        $subject->delete();

        return response()->json(['message' => 'Materia eliminada.']);
    }

    public function upsertProgress(Request $request, StudySubject $subject)
    {
        $electiveKeys = collect(config('study_plan.electives'))
            ->flatten(1)
            ->pluck('key')
            ->all();

        $validated = $request->validate([
            'status'                 => ['nullable', Rule::in(StudySubject::STATUSES)],
            'note'                   => ['nullable', 'string', 'max:255'],
            'selected_elective_key'  => ['nullable', 'string', Rule::in($electiveKeys)],
            'elective_preferences'   => ['nullable', 'array'],
            'elective_preferences.*' => ['nullable', 'string', Rule::in(['alto', 'medio', 'bajo'])],
        ]);

        if (! empty($validated['selected_elective_key'])) {
            abort_unless($subject->is_elective_slot, 422, 'Solo las electivas pueden tener opción seleccionada.');

            $allowed = collect(config('study_plan.electives')[$subject->elective_group] ?? [])
                ->pluck('key')
                ->all();

            abort_unless(
                in_array($validated['selected_elective_key'], $allowed, true),
                422,
                'La opción no pertenece a este grupo de electiva.'
            );
        }

        $preferences = null;
        if ($subject->is_elective_slot) {
            $preferences = collect($validated['elective_preferences'] ?? [])
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->all();
        }

        $subject->update([
            'status'                => $validated['status'] ?? null,
            'note'                  => $validated['note'] ?? null,
            'selected_elective_key' => $validated['selected_elective_key'] ?? null,
            'elective_preferences'  => $preferences ?: null,
        ]);

        return response()->json($subject->fresh());
    }
}
