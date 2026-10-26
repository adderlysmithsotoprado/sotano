<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\BusinessSetting;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Modifier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Services\PromotionService;
use App\Models\CommandBatch;
use App\Models\CommandBatchItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PosController extends Controller
{
   public function show(RestaurantTable $mesa)
{
    $order = Order::query()
        ->where('restaurant_table_id', $mesa->id)
        ->where('status', 'abierto')
        ->first();

    if (!$order) {
        $order = Order::create([
            'restaurant_table_id' => $mesa->id,
            'user_id' => Auth::id(),
            'status' => 'abierto',
            'ready' => false,
            'command_sent' => false,
            'total' => 0,
        ]);

        $mesa->update([
            'status' => 'ocupada',
            'reserved_by' => null,
            'reserved_at' => null,
        ]);
    }

    $order->load([
        'items.modifiers',
        'customer:id,name',
        'user:id,name',
    ]);

    return Inertia::render('POS/Show', [
        'cashRegister' => CashRegister::query()
            ->where('user_id', Auth::id())
            ->where('status', 'abierta')
            ->latest('opened_at')
            ->first(),

        'business' => BusinessSetting::first(),

        'table' => $mesa,

        'order' => $order,

        'tables' => RestaurantTable::query()
            ->select('id', 'name', 'status')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(),

        'customers' => Customer::query()
            ->select('id', 'name', 'phone')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(),

        'hasPendingCommandItems' => $order->items()
            ->where('command_sent', false)
            ->where('status', '!=', 'cancelado')
            ->exists(),

        'categories' => Category::query()
            ->select('id', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(),

        'products' => Product::query()
            ->select([
                'id',
                'category_id',
                'name',
                'price',
                'stock',
                'printer_area',
                'is_favorite',
            ])
            ->with([
                'category:id,name',
                'modifiers:id,name,extra_price',
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(),
    ]);
}

    public function addItem(Request $request, Order $order)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'modifier_ids' => ['nullable', 'array'],
            'modifier_ids.*' => ['exists:modifiers,id'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $quantity = (int) $data['quantity'];
        $unitCost = (float) $product->cost;
        $modifierIds = $data['modifier_ids'] ?? [];

        if ($product->stock < $quantity) {
            return back()->withErrors([
                'stock' => 'No hay stock suficiente para este producto.',
            ]);
        }

        $modifiers = empty($modifierIds)
    ? collect()
    : Modifier::query()
        ->select([
            'id',
            'name',
            'extra_price',
        ])
        ->whereIn('id', $modifierIds)
        ->get();

$extraPrice = (float) $modifiers->sum('extra_price');

$unitPrice = (float) $product->price + $extraPrice;

        $hasModifiers = count($modifierIds) > 0;
        $hasNotes = !empty($data['notes']);

        if (!$hasModifiers && !$hasNotes) {
            $existingItem = OrderItem::where('order_id', $order->id)
    ->where('product_id', $product->id)
    ->where('command_sent', false)
    ->whereNull('notes')
    ->whereDoesntHave('modifiers')
    ->first();

            if ($existingItem) {
                $newQuantity = $existingItem->quantity + $quantity;

                $existingItem->update([
                    'quantity' => $newQuantity,
                    'subtotal' => $newQuantity * $existingItem->unit_price,
                    'profit' => ($existingItem->unit_price - $existingItem->unit_cost) * $newQuantity,
                ]);
            } else {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity,
                    'printer_area' => $product->printer_area,
                    'notes' => null,
                    'unit_cost' => $unitCost,
                    'profit' => ($product->price - $unitCost) * $quantity,
                    'command_sent' => false,
'status' => 'pendiente',
                ]);
            }
        } else {
            $item = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $unitPrice * $quantity,
                'unit_cost' => $unitCost,
                'profit' => ($unitPrice - $unitCost) * $quantity,
                'printer_area' => $product->printer_area,
                'notes' => $data['notes'] ?? null,
                'command_sent' => false,
'status' => 'pendiente',
            ]);

            foreach ($modifiers as $modifier) {
                OrderItemModifier::create([
                    'order_item_id' => $item->id,
                    'modifier_id' => $modifier->id,
                    'modifier_name' => $modifier->name,
                    'extra_price' => $modifier->extra_price,
                ]);
            }
        }

        $this->decreaseStock(
    $product,
    $quantity,
    'venta',
    'Venta - Pedido #' . $order->id,
    $order
);

/*
 * PromotionService ya limpia promociones,
 * recalcula subtotales y actualiza el total.
 */
app(PromotionService::class)->apply($order);

return back();
    }

    public function increaseItem(OrderItem $item)
{
    $order = $item->order;
    $product = Product::find($item->product_id);

    if (!$product || $product->stock < 1) {
        return back()->withErrors([
            'stock' => 'No hay stock suficiente para aumentar este producto.',
        ]);
    }

    if ($item->command_sent) {
        $newItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $item->product_id,
            'product_name' => $item->product_name,
            'unit_price' => $item->unit_price,
            'quantity' => 1,
            'subtotal' => $item->unit_price,
            'printer_area' => $item->printer_area,
            'notes' => $item->notes,
            'unit_cost' => $item->unit_cost,
            'profit' => $item->unit_price - $item->unit_cost,
            'command_sent' => false,
            'status' => 'pendiente',
        ]);

        foreach ($item->modifiers as $modifier) {
            $newModifier = $modifier->replicate();
            $newModifier->order_item_id = $newItem->id;
            $newModifier->save();
        }
    } else {
        $newQuantity = $item->quantity + 1;

        $item->update([
            'quantity' => $newQuantity,
            'subtotal' => $newQuantity * $item->unit_price,
            'profit' => ($item->unit_price - $item->unit_cost) * $newQuantity,
        ]);
    }

    $this->decreaseStock(
        $product,
        1,
        'venta',
        'Aumento de cantidad - Pedido #' . $order->id,
        $order
    );

    $order->update([
        'ready' => false,
        'command_sent' => false,
    ]);

    app(PromotionService::class)->apply($order);

    return back();
}

    public function splitBill(Order $order)
    {
    $order->load([
        'items.modifiers',
        'table',
        'customer',
    ]);

    return Inertia::render('POS/DividirCuenta', [
        'order' => $order,
    ]);
    }

    public function processSplitBill(Request $request, Order $order)
{
    $data = $request->validate([
        'items' => ['required', 'array', 'min:1'],
        'items.*.item_id' => [
            'required',
            'integer',
            'exists:order_items,id',
        ],
        'items.*.quantity' => [
            'required',
            'integer',
            'min:1',
        ],
    ]);

    try {
        $splitOrder = DB::transaction(function () use ($data, $order) {
            $splitOrder = Order::create([
                'restaurant_table_id' => $order->restaurant_table_id,
                'user_id' => Auth::id(),
                'customer_id' => $order->customer_id,
                'guest_name' => 'Cuenta separada - Pedido #' . $order->id,
                'status' => 'abierto',
                'ready' => true,
                'command_sent' => true,
                'total' => 0,
            ]);

            foreach ($data['items'] as $selected) {
                $item = OrderItem::with('modifiers')
                    ->where('order_id', $order->id)
                    ->where('status', '!=', 'cancelado')
                    ->findOrFail($selected['item_id']);

                $quantityToMove = (int) $selected['quantity'];
                $originalQuantity = (int) $item->quantity;

                if ($quantityToMove > $originalQuantity) {
                    throw new \Exception(
                        'La cantidad seleccionada de ' .
                        $item->product_name .
                        ' supera la cantidad disponible.'
                    );
                }

                $unitPrice = (float) $item->unit_price;
                $unitCost = (float) ($item->unit_cost ?? 0);

                /*
                 * Si mueve toda la fila, simplemente cambia
                 * el item al nuevo pedido.
                 */
                if ($quantityToMove === $originalQuantity) {
                    $item->update([
                        'order_id' => $splitOrder->id,
                    ]);

                    continue;
                }

                /*
                 * Crea la parte separada.
                 */
                $newItem = OrderItem::create([
                    'order_id' => $splitOrder->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantityToMove,
                    'subtotal' => round(
                        $unitPrice * $quantityToMove,
                        2
                    ),
                    'printer_area' => $item->printer_area,
                    'notes' => $item->notes,
                    'unit_cost' => $unitCost,
                    'profit' => round(
                        ($unitPrice - $unitCost) * $quantityToMove,
                        2
                    ),
                    'command_sent' => true,
                    'status' => 'entregado',
                    'discount' => 0,
                    'promotion_name' => null,
                    'printed_at' => $item->printed_at,
                    'delivered_at' => $item->delivered_at,
                ]);

                foreach ($item->modifiers as $modifier) {
                    OrderItemModifier::create([
                        'order_item_id' => $newItem->id,
                        'modifier_id' => $modifier->modifier_id,
                        'modifier_name' => $modifier->modifier_name,
                        'extra_price' => $modifier->extra_price,
                    ]);
                }

                /*
                 * Reduce la cantidad del pedido original.
                 */
                $remainingQuantity =
                    $originalQuantity - $quantityToMove;

                $item->update([
                    'quantity' => $remainingQuantity,
                    'subtotal' => round(
                        $unitPrice * $remainingQuantity,
                        2
                    ),
                    'discount' => 0,
                    'promotion_name' => null,
                    'profit' => round(
                        ($unitPrice - $unitCost) * $remainingQuantity,
                        2
                    ),
                ]);
            }

            $originalTotal = $order->items()
                ->where('status', '!=', 'cancelado')
                ->sum('subtotal');

            $splitTotal = $splitOrder->items()
                ->where('status', '!=', 'cancelado')
                ->sum('subtotal');

            $order->update([
                'total' => round((float) $originalTotal, 2),
            ]);

            $splitOrder->update([
                'total' => round((float) $splitTotal, 2),
            ]);

            return $splitOrder;
        });

        return redirect()->route(
            'pos.payment',
            $splitOrder->id
        );
    } catch (\Throwable $exception) {
        report($exception);

        return back()->withErrors([
            'items' => 'No se pudo dividir la cuenta: ' .
                $exception->getMessage(),
        ]);
    }
}

    public function decreaseItem(OrderItem $item)
    {
        $order = $item->order;
        $product = Product::find($item->product_id);

        if ($product) {
            $this->increaseStock($product, 1, 'devolucion', 'Disminución de cantidad - Pedido #' . $order->id, $order);
        }

        if ($item->quantity <= 1) {
            $item->delete();
        } else {
            $newQuantity = $item->quantity - 1;

            $item->update([
                'quantity' => $newQuantity,
                'subtotal' => $newQuantity * $item->unit_price,
                'profit' => ($item->unit_price - $item->unit_cost) * $newQuantity,
            ]);
        }

        $order->update([
            'ready' => false,
            'command_sent' => false,
        ]);

        app(PromotionService::class)->apply($order);

        return back();
    }

    public function removeItem(Request $request, OrderItem $item)
{
    $data = $request->validate([
        'reason' => ['required', 'string', 'max:255'],
    ]);

    if ($item->status === 'cancelado') {
        return back()->withErrors([
            'item' => 'Este producto ya fue cancelado.',
        ]);
    }

    $order = $item->order;
    $product = Product::find($item->product_id);
    $reason = trim($data['reason']);

    DB::transaction(function () use (
        $item,
        $order,
        $product,
        $reason
    ) {
        if ($product) {
            $this->increaseStock(
                $product,
                (int) $item->quantity,
                'cancelacion',
                'Producto cancelado - Pedido #' . $order->id .
                ' | Producto: ' . $item->product_name .
                ' | Motivo: ' . $reason,
                $order
            );
        }

        $item->update([
            'status' => 'cancelado',
            'cancel_reason' => $reason,
            'cancelled_by' => Auth::id(),
            'cancelled_at' => now(),
            'discount' => 0,
            'promotion_name' => null,
            'profit' => 0,
        ]);

        app(PromotionService::class)->apply($order);

        $this->recalculateOrderTotal($order);

        ActivityLogger::log(
            'POS',
            'Canceló producto',
            'Pedido #' . $order->id .
            ' | Producto: ' . $item->product_name .
            ' | Cantidad: ' . $item->quantity .
            ' | Motivo: ' . $reason
        );
    });

    return back()->with(
        'success',
        'Producto cancelado, stock restaurado y total actualizado.'
    );
}
    public function sendCommand(Order $order)
{
    $pendingItems = $order->items()
    ->with('modifiers')
    ->where('command_sent', false)
    ->where('status', '!=', 'cancelado')
    ->get();

    if ($pendingItems->isEmpty()) {
        return back()->withErrors([
            'command' => 'No hay productos nuevos para enviar a comandas.',
        ]);
    }

    $business = BusinessSetting::first();

    try {
        DB::transaction(function () use ($order, $pendingItems, $business) {
            $itemsByArea = $pendingItems->groupBy('printer_area');

            foreach (['cocina', 'bar'] as $area) {
                $areaItems = $itemsByArea->get($area, collect());

                if ($areaItems->isEmpty()) {
                    continue;
                }

                $printerName = $area === 'cocina'
                    ? $business?->kitchen_printer
                    : $business?->bar_printer;

                $batch = CommandBatch::create([
                    'order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'area' => $area,
                    'printer_name' => $printerName,
                    'status' => 'impreso',
                    'sent_at' => now(),
                    'printed_at' => now(),
                    'print_error' => null,
                ]);

                foreach ($areaItems as $item) {
                    CommandBatchItem::create([
                        'command_batch_id' => $batch->id,
                        'order_item_id' => $item->id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'notes' => $item->notes,
                        'modifiers' => $item->modifiers
                            ->map(function ($modifier) {
                                return [
                                    'name' => $modifier->modifier_name,
                                    'extra_price' => $modifier->extra_price,
                                ];
                            })
                            ->values()
                            ->all(),
                        'status' => 'pendiente',
                        'delivered_at' => null,
                    ]);
                }
            }

            foreach ($pendingItems as $item) {
    $item->update([
        'command_sent' => true,
        'status' => 'entregado',
        'printed_at' => now(),
        'delivered_at' => now(),
                ]);
            }

            $pendingAfterSend = $order->items()
    ->where('status', '!=', 'entregado')
    ->count();

$order->update([
    'command_sent' => true,
    'ready' => $pendingAfterSend === 0,
            ]);
        });
    } catch (\Throwable $exception) {
        report($exception);

        return back()->withErrors([
            'command' => 'La impresión se realizó, pero no se pudo registrar el lote de comandas.',
        ]);
    }

    return back()->with(
        'success',
        'Comandas impresas y registradas correctamente.'
    );
}

    public function preCuenta(Order $order)
{
    $order->update([
        'printed_at' => now(),
    ]);

    $order->load([
        'items.modifiers',
        'table',
        'user',
        'customer',
    ]);

    $business = BusinessSetting::first();

    $line = str_repeat('-', 42);

    $content = '';

    $content .= ($business?->business_name ?: 'SOTANO RESTOBAR') . "\r\n";

    if ($business?->ruc) {
        $content .= "RUC: {$business->ruc}\r\n";
    }

    if ($business?->address) {
        $content .= "{$business->address}\r\n";
    }

    $content .= "\r\n";
    $content .= "******** PRE CUENTA ********\r\n";
    $content .= $line . "\r\n";
    $content .= "PEDIDO: #{$order->id}\r\n";
    $content .= "MESA: {$order->table->name}\r\n";
    $content .= "MOZO: {$order->user->name}\r\n";
    $content .= "FECHA: " . now()->format('d/m/Y H:i') . "\r\n";
    $content .= $line . "\r\n";

    foreach (
    $order->items
        ->where('status','!=','cancelado')
    as $item
) {

        $content .= "{$item->quantity} x {$item->product_name}\r\n";

        foreach ($item->modifiers as $modifier) {
            $content .= "   + {$modifier->modifier_name}\r\n";
        }

        if ($item->notes) {
            $content .= "   Nota: {$item->notes}\r\n";
        }

        $content .= "   S/ " . number_format($item->subtotal,2) . "\r\n";
    }

    $content .= $line . "\r\n";

    $content .= "TOTAL: S/ " . number_format($order->total,2) . "\r\n";

    $content .= $line . "\r\n";

    $content .= "ESTE DOCUMENTO NO ES\r\n";
    $content .= "COMPROBANTE DE PAGO\r\n";

    return response()->json([
        'printer_name' => $business?->ticket_printer,
        'ticket_content' => $content,
    ]);
}

    public function payment(Order $order)
{
    $order->load([
        'items.modifiers',
        'table',
        'user',
        'customer',
    ]);

    // En la pantalla de cobro solo se muestran productos vigentes.
    $order->setRelation(
        'items',
        $order->items
            ->where('status', '!=', 'cancelado')
            ->values()
    );

    return Inertia::render('POS/Payment', [
        'order' => $order,

        'cashRegister' => CashRegister::where(
            'user_id',
            Auth::id()
        )
            ->where('status', 'abierta')
            ->latest()
            ->first(),
    ]);
}

    public function close(Request $request, Order $order)
{
    $cashRegister = CashRegister::where('user_id', Auth::id())
        ->where('status', 'abierta')
        ->first();

    if (!$cashRegister) {
        return response()->json([
            'message' => 'Debes abrir caja antes de cobrar una mesa.',
        ], 422);
    }

    $pendingItems = $order->items()
    ->where('status', '!=', 'cancelado')
    ->whereNotIn('status', ['listo', 'entregado'])
    ->count();

    if ($pendingItems > 0) {
        return response()->json([
            'message' => 'Aún hay productos pendientes. No puedes cobrar todavía.',
        ], 422);
    }

    $data = $request->validate([
        'payments' => ['required', 'array', 'min:1'],
        'payments.*.method' => ['required', 'string'],
        'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
        'payments.*.operation_number' => ['nullable', 'string', 'max:100'],
    ]);

    $totalPaid = collect($data['payments'])
        ->sum(fn ($payment) => (float) $payment['amount']);

    $orderTotal = (float) $order->total;
    $changeAmount = max(0, $totalPaid - $orderTotal);

    if (round($totalPaid, 2) < round($orderTotal, 2)) {
        return response()->json([
            'message' => 'El monto pagado es insuficiente.',
        ], 422);
    }

    $order->payments()->delete();

    $remainingTotal = $orderTotal;

    foreach ($data['payments'] as $payment) {
        $amountReceived = (float) $payment['amount'];
        $amountToRegister = min($amountReceived, $remainingTotal);

        if ($amountToRegister <= 0) {
            continue;
        }

        OrderPayment::create([
            'order_id' => $order->id,
            'method' => $payment['method'],
            'amount' => $amountToRegister,
            'operation_number' => $payment['operation_number'] ?? null,
        ]);

        $remainingTotal -= $amountToRegister;
    }

    $order->load([
        'table',
        'customer',
        'user',
        'items.modifiers',
        'payments',
    ]);

    $tableName = $order->table?->name ?? 'Sin mesa';

    $order->update([
        'status' => 'pagado',
        'ready' => true,
        'command_sent' => true,
        'paid_at' => now(),
        'amount_received' => $totalPaid,
        'change_amount' => $changeAmount,
    ]);

    if ($order->customer) {
        $points = floor($orderTotal / 10);

        $order->customer->increment('total_spent', $orderTotal);
        $order->customer->increment('points', $points);
    }

    if ($order->table) {
        $order->table->update([
            'status' => 'libre',
            'reserved_by' => null,
            'reserved_at' => null,
        ]);
    }

    ActivityLogger::log(
        'POS',
        'Cobró pedido',
        'Pedido #' . $order->id . ' | Total: S/ ' . number_format($orderTotal, 2)
    );

    $business = BusinessSetting::first();



    $line = str_repeat('-', 42);
    $content = '';

    $content .= ($business->business_name ?: 'SOTANO RESTOBAR') . "\r\n";

    if ($business->ruc) {
        $content .= 'RUC: ' . $business->ruc . "\r\n";
    }

    if ($business->address) {
        $content .= $business->address . "\r\n";
    }

    if ($business->phone) {
        $content .= 'TEL: ' . $business->phone . "\r\n";
    }

    $content .= "\r\n";
    $content .= "TICKET DE VENTA\r\n";
    $content .= $line . "\r\n";
    $content .= 'PEDIDO: #' . $order->id . "\r\n";
    $content .= 'MESA: ' . $tableName . "\r\n";
    $content .= 'CAJERO: ' . ($order->user?->name ?? 'Administrador') . "\r\n";

    if ($order->customer) {
        $content .= 'CLIENTE: ' . $order->customer->name . "\r\n";
    }

    $content .= 'FECHA: ' . now()
        ->timezone('America/Lima')
        ->format('d/m/Y H:i') . "\r\n";

    $content .= $line . "\r\n";

    foreach (
    $order->items
        ->where('status','!=','cancelado')
    as $item
) {
        $content .= $item->quantity . ' x ' .
            mb_strtoupper($item->product_name) . "\r\n";

        foreach ($item->modifiers as $modifier) {
            $content .= '   + ' . $modifier->modifier_name . "\r\n";
        }

        if ($item->notes) {
            $content .= '   NOTA: ' . $item->notes . "\r\n";
        }

        $content .= '   P.U. S/ ' .
            number_format((float) $item->unit_price, 2) .
            '   TOTAL S/ ' .
            number_format((float) $item->subtotal, 2) .
            "\r\n";
    }

    $content .= $line . "\r\n";
    $content .= "FORMA DE PAGO\r\n";

    foreach ($order->payments as $payment) {
        $content .= mb_strtoupper($payment->method);

        if ($payment->operation_number) {
            $content .= ' (' . $payment->operation_number . ')';
        }

        $content .= ': S/ ' .
            number_format((float) $payment->amount, 2) .
            "\r\n";
    }

    $content .= $line . "\r\n";
    $content .= 'TOTAL: S/ ' .
        number_format($orderTotal, 2) .
        "\r\n";

    $content .= 'RECIBIDO: S/ ' .
        number_format($totalPaid, 2) .
        "\r\n";

    if ($changeAmount > 0) {
        $content .= 'VUELTO: S/ ' .
            number_format($changeAmount, 2) .
            "\r\n";
    }

    $content .= $line . "\r\n";
    $content .= ($business->ticket_message ?: 'Gracias por su preferencia') . "\r\n";
    $content .= "VUELVA PRONTO\r\n";

    return response()->json([
        'success' => true,
        'order_id' => $order->id,
        'printer_name' => $business?->ticket_printer ?? '',
        'ticket_content' => $content,
        'redirect_url' => route('mesas'),
    ]);
}

    public function cancelOrder(Request $request, Order $order)
    {
        if ($order->status !== 'abierto') {
            return back()->withErrors([
                'order' => 'Solo se pueden cancelar pedidos abiertos.',
            ]);
        }

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ]);

        $order->load(['items', 'table']);

        foreach ($order->items as $item) {
            $product = Product::find($item->product_id);

            if ($product) {
                $this->increaseStock(
                    $product,
                    $item->quantity,
                    'cancelacion',
                    'Cancelación - Pedido #' . $order->id,
                    $order
                );
            }

            $item->delete();
        }

        $order->update([
            'status' => 'cancelado',
            'ready' => false,
            'command_sent' => false,
            'total' => 0,
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id(),
            'cancel_reason' => $data['cancel_reason'],
        ]);

        if ($order->table) {
            $order->table->update([
                'status' => 'libre',
                'reserved_by' => null,
                'reserved_at' => null,
            ]);
        }

        ActivityLogger::log(
            'POS',
            'Canceló pedido',
            'Pedido #' . $order->id .
            ' | Mesa: ' . ($order->table?->name ?? '-') .
            ' | Motivo: ' . $data['cancel_reason']
        );

        return redirect()->route('mesas');
    }

    public function updateCustomer(Request $request, Order $order)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
        ]);

        $order->update([
            'customer_id' => $data['customer_id'] ?? null,
        ]);

        return back();
    }

    public function updateGuestName(Request $request, Order $order)
    {
    $data = $request->validate([
        'guest_name' => ['nullable', 'string', 'max:100'],
    ]);

    $order->update([
        'guest_name' => $data['guest_name'] ?? null,
    ]);

    return back();
    }

    public function moveItemToTable(Request $request, OrderItem $item)
    {
    $data = $request->validate([
        'target_table_id' => ['required', 'exists:restaurant_tables,id'],
        'quantity' => ['required', 'integer', 'min:1'],
    ]);

    if ($data['quantity'] > $item->quantity) {
        return back()->withErrors([
            'item' => 'La cantidad a mover no puede ser mayor a la cantidad del producto.',
        ]);
    }

    $sourceOrder = $item->order;
    $targetTable = RestaurantTable::findOrFail($data['target_table_id']);

    $targetOrder = Order::where('restaurant_table_id', $targetTable->id)
        ->where('status', 'abierto')
        ->first();

    if (!$targetOrder) {
        $targetOrder = Order::create([
            'restaurant_table_id' => $targetTable->id,
            'user_id' => Auth::id(),
            'status' => 'abierto',
            'ready' => false,
            'command_sent' => false,
            'total' => 0,
        ]);
    }

    if ((int) $data['quantity'] === (int) $item->quantity) {
        $item->update([
            'order_id' => $targetOrder->id,
        ]);
    } else {
        $newQuantity = (int) $data['quantity'];

        $newItem = $item->replicate();
        $newItem->order_id = $targetOrder->id;
        $newItem->quantity = $newQuantity;
        $newItem->subtotal = $item->unit_price * $newQuantity;
        $newItem->profit = ($item->unit_price - $item->unit_cost) * $newQuantity;
        $newItem->save();

        foreach ($item->modifiers as $modifier) {
            $newModifier = $modifier->replicate();
            $newModifier->order_item_id = $newItem->id;
            $newModifier->save();
        }

        $remainingQuantity = $item->quantity - $newQuantity;

        $item->update([
            'quantity' => $remainingQuantity,
            'subtotal' => $item->unit_price * $remainingQuantity,
            'profit' => ($item->unit_price - $item->unit_cost) * $remainingQuantity,
        ]);
    }

    $targetTable->update([
        'status' => 'ocupada',
        'reserved_by' => null,
        'reserved_at' => null,
    ]);

    if ($sourceOrder->items()->count() === 0) {
        $sourceOrder->update([
            'status' => 'cancelado',
            'total' => 0,
            'cancel_reason' => 'Productos movidos a otra mesa',
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id(),
        ]);

        if ($sourceOrder->table) {
            $sourceOrder->table->update([
                'status' => 'libre',
            ]);
        }
    } else {
        $this->recalculateOrderTotal($sourceOrder);
    }

    $this->recalculateOrderTotal($targetOrder);

    return back()->with('success', 'Producto movido correctamente.');
    }

    public function updateItemNote(Request $request, OrderItem $item)
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $item->update([
            'notes' => $data['notes'] ?? null,
        ]);

        return back();
    }



    private function recalculateOrderTotal(Order $order): void
{
    $total = $order->items()
        ->where('status', '!=', 'cancelado')
        ->sum('subtotal');

    $order->update([
        'total' => $total,
    ]);
}

    private function decreaseStock(
    Product $product,
    int $quantity,
    string $type,
    string $reason,
    Order $order
): void {
    $stockBefore = (int) $product->stock;
    $stockAfter = $stockBefore - $quantity;

    $product->update([
        'stock' => $stockAfter,
    ]);

    StockMovement::create([
        'product_id' => $product->id,
        'user_id' => Auth::id(),
        'type' => $type,
        'quantity' => -$quantity,
        'stock_before' => $stockBefore,
        'stock_after' => $stockAfter,
        'reason' => $reason,
        'reference_type' => Order::class,
        'reference_id' => $order->id,
    ]);
}

    private function increaseStock(
    Product $product,
    int $quantity,
    string $type,
    string $reason,
    Order $order
): void {
    $stockBefore = (int) $product->stock;
    $stockAfter = $stockBefore + $quantity;

    $product->update([
        'stock' => $stockAfter,
    ]);

    StockMovement::create([
        'product_id' => $product->id,
        'user_id' => Auth::id(),
        'type' => $type,
        'quantity' => $quantity,
        'stock_before' => $stockBefore,
        'stock_after' => $stockAfter,
        'reason' => $reason,
        'reference_type' => Order::class,
        'reference_id' => $order->id,
    ]);
}
}
