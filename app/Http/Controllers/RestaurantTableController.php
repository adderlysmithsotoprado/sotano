<?php

namespace App\Http\Controllers;

use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RestaurantTableController extends Controller
{
    public function index()
{
    $tables = RestaurantTable::query()
        ->select([
            'id',
            'name',
            'capacity',
            'status',
            'reserved_by',
            'reserved_at',
            'is_active',
        ])
        ->with([
            'orders' => function ($query) {
                $query
                    ->select([
                        'id',
                        'restaurant_table_id',
                        'guest_name',
                        'customer_id',
                        'total',
                        'status',
                        'ready',
                        'command_sent',
                        'created_at',
                    ])
                    ->where('status', 'abierto')
                    ->latest('id');
            },
            'orders.customer:id,name',
        ])
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    return Inertia::render('Mesas/Index', [
        'tables' => $tables,
    ]);
}

    public function merge(Request $request, RestaurantTable $table)
    {
    $data = $request->validate([
        'source_table_id' => ['required', 'exists:restaurant_tables,id'],
    ]);

    $sourceTable = RestaurantTable::findOrFail($data['source_table_id']);

    if ($sourceTable->id === $table->id) {
        return back()->withErrors([
            'table' => 'No puedes unir la misma mesa.',
        ]);
    }

    $targetOrder = $table->orders()
        ->where('status', 'abierto')
        ->first();

    $sourceOrder = $sourceTable->orders()
        ->where('status', 'abierto')
        ->first();

    if (!$targetOrder || !$sourceOrder) {
        return back()->withErrors([
            'table' => 'Ambas mesas deben tener pedidos abiertos.',
        ]);
    }

    $sourceOrder->items()->update([
        'order_id' => $targetOrder->id,
    ]);

    $targetOrder->update([
        'total' => $targetOrder->items()
    ->where('status', '!=', 'cancelado')
    ->sum('subtotal'),
        'ready' => false,
        'command_sent' => false,
    ]);

    $sourceOrder->update([
        'status' => 'cancelado',
        'total' => 0,
        'cancel_reason' => 'Pedido unido a ' . $table->name,
        'cancelled_at' => now(),
        'cancelled_by' => auth()->id(),
    ]);

    $sourceTable->update([
        'status' => 'libre',
        'reserved_by' => null,
        'reserved_at' => null,
    ]);

    $table->update([
        'status' => 'ocupada',
    ]);

    return back()->with('success', 'Mesas unidas correctamente.');
    }

    public function update(Request $request, RestaurantTable $mesa)
{
    $data = $request->validate([
        'name' => [
            'required',
            'max:50',
            Rule::unique(
                'restaurant_tables',
                'name'
            )->ignore($mesa->id),
        ],
        'capacity' => [
            'required',
            'integer',
            'min:1',
        ],
        'status' => [
            'required',
            'in:libre,ocupada,reservada',
        ],
        'reserved_by' => [
            'nullable',
            'string',
            'max:100',
        ],
        'reserved_at' => [
            'nullable',
            'date',
        ],
        'is_active' => [
            'boolean',
        ],
    ]);

    $data['is_active'] = $request->boolean('is_active');

    if ($data['status'] !== 'reservada') {
        $data['reserved_by'] = null;
        $data['reserved_at'] = null;
    }

    if ($data['status'] === 'libre') {
        $openOrder = $mesa->orders()
            ->with(['items.product'])
            ->where('status', 'abierto')
            ->first();

        if ($openOrder) {
            DB::transaction(function () use (
                $openOrder
            ) {
                foreach ($openOrder->items as $item) {
                    if ($item->product) {
                        $item->product->increment(
                            'stock',
                            $item->quantity
                        );
                    }
                }

                $openOrder->items()->delete();

                $openOrder->update([
                    'status' => 'cancelado',
                    'ready' => true,
                    'command_sent' => true,
                    'total' => 0,
                    'cancel_reason' =>
                        'Pedido cancelado al liberar la mesa manualmente.',
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                ]);
            });
        }
    }

    $mesa->update($data);

    return redirect()
        ->route('mesas')
        ->with(
            'success',
            'Mesa actualizada correctamente.'
        );
}

    public function destroy(RestaurantTable $mesa)
{
    $hasOpenOrder = $mesa->orders()
        ->where('status', 'abierto')
        ->exists();

    if ($hasOpenOrder) {
        return back()->withErrors([
            'table' =>
                'No puedes eliminar una mesa con un pedido abierto.',
        ]);
    }

    $mesa->delete();

    return back()->with(
        'success',
        'Mesa eliminada correctamente.'
    );
}

    public function clearReservation(RestaurantTable $mesa)
    {
        $mesa->update([
            'status' => 'libre',
            'reserved_by' => null,
            'reserved_at' => null,
        ]);

        return back();
    }

    public function edit(RestaurantTable $mesa)
    {
        return Inertia::render('Mesas/Edit', [
            'table' => $mesa,
        ]);
    }
    public function move(Request $request, RestaurantTable $table)
    {
    $data = $request->validate([
        'target_table_id' => ['required', 'exists:restaurant_tables,id'],
    ]);

    $targetTable = RestaurantTable::findOrFail($data['target_table_id']);

    $order = $table->orders()
        ->where('status', 'abierto')
        ->first();

    if (!$order) {
        return back()->withErrors([
            'table' => 'Esta mesa no tiene pedido abierto.',
        ]);
    }

    $targetHasOrder = $targetTable->orders()
        ->where('status', 'abierto')
        ->exists();

    if ($targetHasOrder) {
        return back()->withErrors([
            'table' => 'La mesa destino ya tiene un pedido abierto.',
        ]);
    }

    $order->update([
        'restaurant_table_id' => $targetTable->id,
    ]);

    $table->update([
        'status' => 'libre',
    ]);

    $targetTable->update([
        'status' => 'ocupada',
        'reserved_by' => null,
        'reserved_at' => null,
    ]);

    return back()->with('success', 'Pedido movido correctamente.');
    }
}
