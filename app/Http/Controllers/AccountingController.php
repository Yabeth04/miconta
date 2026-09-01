<?php
namespace App\Http\Controllers;

use App\Imports\AccountingMovementsImport;
use App\Imports\AccountingWorkbookImport;
use App\Models\Accounting;
use App\Models\AccountingConcept;
use App\Models\AccountingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class AccountingController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
            'concept'   => ['nullable', 'string', 'max:255'],
            'q'         => ['nullable', 'string', 'max:255'],
        ]);

        $filtered = $this->filteredMovements($request);

        $accounting = (clone $filtered)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10);

        $payload = $accounting->toArray();

        if ($accounting->currentPage() === 1) {
            $totals = (clone $filtered)
                ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'debe' THEN amount ELSE 0 END), 0) as total_debe")
                ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'haber' THEN amount ELSE 0 END), 0) as total_haber")
                ->selectRaw('COUNT(*) as count_total')
                ->first();

            $opening = (float) $this->settings($request)->opening_balance_main;
            $userId  = $request->user()->id;

            $global = DB::table('accounting_movements')
                ->where('user_id', $userId)
                ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'debe' THEN amount ELSE 0 END), 0) as total_debe")
                ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'haber' THEN amount ELSE 0 END), 0) as total_haber")
                ->first();

            $payload['totals'] = [
                'debe'            => (float) $totals->total_debe,
                'haber'           => (float) $totals->total_haber,
                'count'           => (int) $totals->count_total,
                'opening_balance' => $opening,
                'account_balance' => $opening
                 + (float) $global->total_haber
                 - (float) $global->total_debe,
            ];
        }

        return response()->json($payload, 200);
    }

    public function showSettings(Request $request)
    {
        $settings = $this->settings($request);

        return response()->json([
            'opening_balance_main' => (float) $settings->opening_balance_main,
        ], 200);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'opening_balance_main' => ['required', 'numeric'],
        ]);

        $settings                       = $this->settings($request);
        $settings->opening_balance_main = $validated['opening_balance_main'];
        $settings->save();

        return response()->json([
            'opening_balance_main' => (float) $settings->opening_balance_main,
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $this->resolveConcept($request, $this->validateAccounting($request));

        DB::table('accounting_movements')->insert(array_merge($validated, [
            'user_id'    => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return response()->json(['message' => 'Movimiento creado.'], 201);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $importId  = (string) Str::uuid();
        $extension = $request->file('file')->getClientOriginalExtension() ?: 'xlsx';
        $path      = $request->file('file')->storeAs(
            'imports/accounting',
            "{$importId}.{$extension}",
            'local'
        );

        try {
            $fullPath        = Storage::disk('local')->path($path);
            $sheetName       = $this->resolvePrincipalSheetName($fullPath);
            $movementsImport = new AccountingMovementsImport($request->user()->id);

            Excel::import(
                new AccountingWorkbookImport($sheetName, $movementsImport),
                $path,
                'local'
            );

            if ($movementsImport->imported === 0) {
                return response()->json([
                    'imported' => 0,
                    'errors'   => $movementsImport->errors,
                    'message'  => 'No se importó ningún movimiento.',
                ], 422);
            }

            return response()->json([
                'imported' => $movementsImport->imported,
                'errors'   => $movementsImport->errors,
            ], 201);
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    public function show(Request $request, Accounting $accounting)
    {
        $this->authorizeMovement($request, $accounting);

        return response()->json($accounting, 200);
    }

    public function update(Request $request, Accounting $accounting)
    {
        $this->authorizeMovement($request, $accounting);
        $validated = $this->resolveConcept($request, $this->validateAccounting($request));

        $accounting->update($validated);

        return response()->json($accounting->fresh(), 200);
    }

    public function destroy(Request $request, Accounting $accounting)
    {
        $this->authorizeMovement($request, $accounting);
        $accounting->delete();

        return response()->json(['message' => 'Movimiento eliminado.'], 200);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids'           => ['required', 'array', 'min:1'],
            'ids.*'         => ['integer'],
            'movement_type' => ['sometimes', 'in:haber,debe'],
            'payment_type'  => ['sometimes', 'in:sinpe,efectivo,transferencia,tarjeta,otros'],
            'concept'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'detail'        => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));
        unset($validated['ids']);

        if ($validated === []) {
            return response()->json(['message' => 'Indicá al menos un campo para actualizar.'], 422);
        }

        $movements = Accounting::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $ids)
            ->get();

        abort_unless($movements->count() === count($ids), 404);

        $payload = $this->bulkUpdatePayload($request, $validated);

        foreach ($movements as $movement) {
            $movement->update($payload);
        }

        return response()->json([
            'updated' => $movements->count(),
        ], 200);
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));

        $deleted = Accounting::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $ids)
            ->delete();

        abort_unless($deleted === count($ids), 404);

        return response()->json([
            'deleted' => $deleted,
        ], 200);
    }

    private function bulkUpdatePayload(Request $request, array $input): array
    {
        $payload = [];

        if (array_key_exists('movement_type', $input)) {
            $payload['movement_type'] = $input['movement_type'];
        }

        if (array_key_exists('payment_type', $input)) {
            $payload['payment_type'] = $input['payment_type'];
        }

        if (array_key_exists('detail', $input)) {
            $rawDetail = trim((string) ($input['detail'] ?? ''));
            $payload['detail'] = $rawDetail === '' ? null : $rawDetail;
        }

        if (array_key_exists('concept', $input)) {
            $resolved = $this->resolveConcept($request, [
                'concept' => $input['concept'],
                'detail'  => $payload['detail'] ?? null,
            ]);

            $payload['concept'] = $resolved['concept'];
            $payload['accounting_concept_id'] = $resolved['accounting_concept_id'];
        }

        return $payload;
    }

    private function settings(Request $request): AccountingSetting
    {
        return AccountingSetting::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['opening_balance_main' => 0],
        );
    }

    private function filteredMovements(Request $request)
    {
        $query = DB::table('accounting_movements')
            ->where('user_id', $request->user()->id);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to')->toDateString());
        }

        $movementTypes = $this->listParam($request, 'movement_type', ['haber', 'debe']);
        if ($movementTypes !== []) {
            $query->whereIn('movement_type', $movementTypes);
        }

        $paymentTypes = $this->listParam($request, 'payment_type', ['sinpe', 'efectivo', 'transferencia', 'tarjeta', 'otros']);
        if ($paymentTypes !== []) {
            $query->whereIn('payment_type', $paymentTypes);
        }

        if ($request->filled('concept')) {
            $term = addcslashes(trim((string) $request->input('concept')), '%_\\');
            $query->where('concept', 'like', "%{$term}%");
        }

        if ($request->filled('q')) {
            $term = addcslashes(trim((string) $request->input('q')), '%_\\');
            $query->where(function ($q) use ($term) {
                $q->where('concept', 'like', "%{$term}%")
                    ->orWhere('detail', 'like', "%{$term}%");
            });
        }

        return $query;
    }

    /**
     * @param  list<string>  $allowed
     * @return list<string>
     */
    private function listParam(Request $request, string $key, array $allowed): array
    {
        $value = $request->input($key);

        if ($value === null || $value === '') {
            return [];
        }

        $items = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_intersect($allowed, array_map('strval', $items)));
    }

    private function validateAccounting(Request $request): array
    {
        return $request->validate([
            'date'          => ['required', 'date'],
            'movement_type' => ['required', 'in:haber,debe'],
            'payment_type'  => ['required', 'in:sinpe,efectivo,transferencia,tarjeta,otros'],
            'amount'        => ['required', 'numeric', 'min:0'],
            'concept'       => ['nullable', 'string', 'max:255'],
            'detail'        => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * Si el concepto coincide con uno fijo del usuario, lo asocia.
     * Si no, guarda el texto libre sin relación.
     */
    private function resolveConcept(Request $request, array $validated): array
    {
        $rawConcept = isset($validated['concept']) ? trim((string) $validated['concept']) : '';
        $rawDetail  = isset($validated['detail']) ? trim((string) $validated['detail']) : '';

        $validated['detail'] = $rawDetail === '' ? null : $rawDetail;

        if ($rawConcept === '') {
            $validated['concept'] = null;
            $validated['accounting_concept_id'] = null;

            return $validated;
        }

        $match = AccountingConcept::query()
            ->where('user_id', $request->user()->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($rawConcept)])
            ->first();

        if ($match) {
            $validated['concept'] = $match->name;
            $validated['accounting_concept_id'] = $match->id;
        } else {
            $validated['concept'] = $rawConcept;
            $validated['accounting_concept_id'] = null;
        }

        return $validated;
    }

    private function authorizeMovement(Request $request, Accounting $accounting): void
    {
        abort_unless((int) $accounting->user_id === (int) $request->user()->id, 404);
    }

    private function resolvePrincipalSheetName(string $fullPath): string
    {
        $reader = IOFactory::createReaderForFile($fullPath);
        $names  = $reader->listWorksheetNames($fullPath);

        foreach ($names as $name) {
            if (mb_strtolower(trim($name)) === 'principal') {
                return $name;
            }
        }

        throw new RuntimeException(
            'No se encontró la hoja "Principal". Hojas: ' . implode(', ', $names)
        );
    }
}
