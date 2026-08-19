<?php
namespace App\Http\Controllers;

use App\Imports\AccountingMovementsImport;
use App\Imports\AccountingWorkbookImport;
use App\Models\Accounting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class AccountingController extends Controller
{
    public function index()
    {
        // para listar los movimientos contables
        $accounting = DB::table('accounting_movements')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10);

        $payload = $accounting->toArray();

        // totales globales (SUM en BD; no carga todas las filas)
        if ($accounting->currentPage() === 1) {
            $totals = DB::table('accounting_movements')
                ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'debe' THEN amount ELSE 0 END), 0) as total_debe")
                ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'haber' THEN amount ELSE 0 END), 0) as total_haber")
                ->selectRaw('COUNT(*) as count_total')
                ->first();

            $payload['totals'] = [
                'debe'  => (float) $totals->total_debe,
                'haber' => (float) $totals->total_haber,
                'count' => (int) $totals->count_total,
            ];
        }

        return response()->json($payload, 200);
    }

    public function store(Request $request)
    {
        // para crear un movimiento contable
        $validated = $this->validateAccounting($request);

        $accounting = DB::table('accounting_movements')
            ->insert(array_merge($validated, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

        return response()->json($accounting, 201);
    }

    /**
     * Importa la hoja Principal del Excel y responde al terminar.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $importId = (string) Str::uuid();
        $extension = $request->file('file')->getClientOriginalExtension() ?: 'xlsx';
        $path = $request->file('file')->storeAs(
            'imports/accounting',
            "{$importId}.{$extension}",
            'local'
        );

        try {
            $fullPath = Storage::disk('local')->path($path);
            $sheetName = $this->resolvePrincipalSheetName($fullPath);
            $movementsImport = new AccountingMovementsImport;

            Excel::import(
                new AccountingWorkbookImport($sheetName, $movementsImport),
                $path,
                'local'
            );

            if ($movementsImport->imported === 0) {
                return response()->json([
                    'imported' => 0,
                    'errors' => $movementsImport->errors,
                    'message' => 'No se importó ningún movimiento.',
                ], 422);
            }

            return response()->json([
                'imported' => $movementsImport->imported,
                'errors' => $movementsImport->errors,
            ], 201);
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    public function show(Accounting $accounting)
    {
        // para mostrar un movimiento contable
        $accounting = DB::table('accounting_movements')
            ->where('id', $accounting->id)
            ->first();

        return response()->json($accounting, 200);
    }

    public function update(Request $request, Accounting $accounting)
    {
        // para actualizar un movimiento contable
        $validated = $this->validateAccounting($request);

        $accounting = DB::table('accounting_movements')
            ->where('id', $accounting->id)
            ->update($validated);

        return response()->json($accounting, 200);
    }

    public function destroy(Accounting $accounting)
    {
        // para eliminar un movimiento contable
        $accounting = DB::table('accounting_movements')
            ->where('id', $accounting->id)
            ->delete();

        return response()->json($accounting, 200);
    }

    private function validateAccounting(Request $request)
    {
        return $request->validate([
            'date'          => ['required', 'date'],
            'movement_type' => ['required', 'in:haber,debe'],
            'payment_type'  => ['required', 'in:sinpe,efectivo,transferencia,tarjeta,otros'],
            'amount'        => ['required', 'numeric', 'min:0'],
            'description'   => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function resolvePrincipalSheetName(string $fullPath): string
    {
        $reader = IOFactory::createReaderForFile($fullPath);
        $names = $reader->listWorksheetNames($fullPath);

        foreach ($names as $name) {
            if (mb_strtolower(trim($name)) === 'principal') {
                return $name;
            }
        }

        throw new RuntimeException(
            'No se encontró la hoja "Principal". Hojas: '.implode(', ', $names)
        );
    }
}
