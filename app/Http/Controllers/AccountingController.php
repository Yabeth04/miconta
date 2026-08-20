<?php
namespace App\Http\Controllers;

use App\Imports\AccountingMovementsImport;
use App\Imports\AccountingWorkbookImport;
use App\Models\Accounting;
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
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
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
        $validated = $this->validateAccounting($request);

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
        $validated = $this->validateAccounting($request);

        $accounting->update($validated);

        return response()->json($accounting->fresh(), 200);
    }

    public function destroy(Request $request, Accounting $accounting)
    {
        $this->authorizeMovement($request, $accounting);
        $accounting->delete();

        return response()->json(['message' => 'Movimiento eliminado.'], 200);
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

        if ($request->filled('description')) {
            $term = addcslashes(trim((string) $request->input('description')), '%_\\');
            $query->where('description', 'like', "%{$term}%");
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
            'description'   => ['nullable', 'string', 'max:255'],
        ]);
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
