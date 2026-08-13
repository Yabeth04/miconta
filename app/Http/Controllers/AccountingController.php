<?php
namespace App\Http\Controllers;

use App\Jobs\ImportAccountingExcelJob;
use App\Models\Accounting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountingController extends Controller
{
    public function index()
    {
        // para listar los movimientos contables
        $accounting = DB::table('accounting')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10);

        $payload = $accounting->toArray();

        // totales globales (SUM en BD; no carga todas las filas)
        if ($accounting->currentPage() === 1) {
            $totals = DB::table('accounting')
                ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'debe' THEN amount ELSE 0 END), 0) as total_debe")
                ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'haber' THEN amount ELSE 0 END), 0) as total_haber")
                ->first();

            $payload['totals'] = [
                'debe'  => (float) $totals->total_debe,
                'haber' => (float) $totals->total_haber,
            ];
        }

        return response()->json($payload, 200);
    }

    public function store(Request $request)
    {
        // para crear un movimiento contable
        $validated = $this->validateAccounting($request);

        $accounting = DB::table('accounting')
            ->insert(array_merge($validated, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

        return response()->json($accounting, 201);
    }

    /**
     * Sube el Excel y encola un job de importación en segundo plano.
     */
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

        Cache::put("accounting-import:{$importId}", [
            'id'       => $importId,
            'status'   => 'queued',
            'progress' => 0,
            'total'    => 0,
            'imported' => 0,
            'message'  => 'En cola...',
            'errors'   => [],
        ], now()->addHours(2));

        ImportAccountingExcelJob::dispatch($importId, 'local', $path);

        return response()->json([
            'import_id' => $importId,
        ], 202);
    }

    /**
     * Estado / progreso del job de importación.
     */
    public function importStatus(string $importId)
    {
        $status = Cache::get("accounting-import:{$importId}");

        if (! $status) {
            return response()->json([
                'message' => 'Importación no encontrada.',
            ], 404);
        }

        return response()->json($status, 200);
    }

    public function show(Accounting $accounting)
    {
        // para mostrar un movimiento contable
        $accounting = DB::table('accounting')
            ->where('id', $accounting->id)
            ->first();

        return response()->json($accounting, 200);
    }

    public function update(Request $request, Accounting $accounting)
    {
        // para actualizar un movimiento contable
        $validated = $this->validateAccounting($request);

        $accounting = DB::table('accounting')
            ->where('id', $accounting->id)
            ->update($validated);

        return response()->json($accounting, 200);
    }

    public function destroy(Accounting $accounting)
    {
        // para eliminar un movimiento contable
        $accounting = DB::table('accounting')
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
}
