<?php
namespace App\Http\Controllers;

use App\Models\FixedPayment;
use App\Models\FixedPaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FixedPaymentController extends Controller
{
    public function index(Request $request)
    {
        $userId   = $request->user()->id;
        $settings = $this->settings($request);

        $items = FixedPayment::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('payment_group')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn(FixedPayment $item) => $this->serializeItem($item));

        $grouped = $items->groupBy('payment_group');
        $primero = (float) collect($grouped->get('primero', []))->sum('amount');
        $segundo = (float) collect($grouped->get('segundo', []))->sum('amount');
        $total   = $primero + $segundo;
        $salary  = (float) $settings->monthly_salary;

        return response()->json([
            'settings' => [
                'monthly_salary' => $salary,
            ],
            'groups'   => [
                'primero' => $grouped->get('primero', collect())->values(),
                'segundo' => $grouped->get('segundo', collect())->values(),
            ],
            'totals'   => [
                'primero'   => $primero,
                'segundo'   => $segundo,
                'expenses'  => $total,
                'remaining' => $salary - $total,
            ],
        ], 200);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'monthly_salary' => ['required', 'numeric', 'min:0'],
        ]);

        $settings                 = $this->settings($request);
        $settings->monthly_salary = $validated['monthly_salary'];
        $settings->save();

        return response()->json([
            'monthly_salary' => (float) $settings->monthly_salary,
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $this->validateItem($request);

        $sortOrder = FixedPayment::query()
            ->where('user_id', $request->user()->id)
            ->where('payment_group', $validated['payment_group'])
            ->max('sort_order');

        $item = FixedPayment::query()->create([
            'user_id'       => $request->user()->id,
            'description'   => $validated['description'],
            'amount'        => $validated['amount'],
            'payment_group' => $validated['payment_group'],
            'due_label'     => $validated['due_label'],
            'sort_order'    => ($sortOrder ?? -1) + 1,
            'is_active'     => true,
        ]);

        return response()->json($this->serializeItem($item), 201);
    }

    public function update(Request $request, FixedPayment $fixedPayment)
    {
        $this->authorizeItem($request, $fixedPayment);
        $validated = $this->validateItem($request, partial: true);

        $fixedPayment->update($validated);

        return response()->json($this->serializeItem($fixedPayment->fresh()), 200);
    }

    public function destroy(Request $request, FixedPayment $fixedPayment)
    {
        $this->authorizeItem($request, $fixedPayment);
        $fixedPayment->delete();

        return response()->json(['message' => 'Pago eliminado.'], 200);
    }

    private function settings(Request $request): FixedPaymentSetting
    {
        return FixedPaymentSetting::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['monthly_salary' => 0],
        );
    }

    private function validateItem(Request $request, bool $partial = false): array
    {
        $rules = [
            'description'   => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'amount'        => [$partial ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'payment_group' => [$partial ? 'sometimes' : 'required', Rule::in(FixedPayment::GROUPS)],
            'due_label'     => [$partial ? 'sometimes' : 'required', 'string', 'max:50'],
        ];

        $validated = $request->validate($rules);

        if (isset($validated['description'])) {
            $validated['description'] = trim($validated['description']);
        }

        return $validated;
    }

    private function authorizeItem(Request $request, FixedPayment $item): void
    {
        abort_unless((int) $item->user_id === (int) $request->user()->id, 404);
    }

    private function serializeItem(FixedPayment $item): array
    {
        return [
            'id'            => $item->id,
            'description'   => $item->description,
            'amount'        => (float) $item->amount,
            'payment_group' => $item->payment_group,
            'due_label'     => $item->due_label,
            'sort_order'    => (int) $item->sort_order,
        ];
    }
}
