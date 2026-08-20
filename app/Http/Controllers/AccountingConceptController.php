<?php

namespace App\Http\Controllers;

use App\Models\AccountingConcept;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountingConceptController extends Controller
{
    public function index(Request $request)
    {
        $items = AccountingConcept::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['data' => $items], 200);
    }

    public function store(Request $request)
    {
        $validated = $this->validateName($request);

        $item = AccountingConcept::query()->create([
            'user_id' => $request->user()->id,
            'name'    => $validated['name'],
        ]);

        return response()->json($item, 201);
    }

    public function update(Request $request, AccountingConcept $concept)
    {
        $this->authorizeConcept($request, $concept);
        $validated = $this->validateName($request, $concept->id);

        $concept->update(['name' => $validated['name']]);

        return response()->json($concept->fresh(), 200);
    }

    public function destroy(Request $request, AccountingConcept $concept)
    {
        $this->authorizeConcept($request, $concept);
        $concept->delete();

        return response()->json(['message' => 'Concepto eliminado.'], 200);
    }

    private function validateName(Request $request, ?int $ignoreId = null): array
    {
        $name = trim((string) $request->input('name', ''));
        $request->merge(['name' => $name]);

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('accounting_concepts', 'name')
                    ->where(fn ($q) => $q->where('user_id', $request->user()->id))
                    ->ignore($ignoreId),
            ],
        ]);
    }

    private function authorizeConcept(Request $request, AccountingConcept $concept): void
    {
        abort_unless((int) $concept->user_id === (int) $request->user()->id, 404);
    }
}
