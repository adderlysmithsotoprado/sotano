<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class PurchaseController extends Controller
{
    public function index()
    {
        return Inertia::render('Purchases/Index', [
            'purchases' => Purchase::with(['supplier', 'user', 'items.product'])
                ->latest()
                ->get(),

            'suppliers' => Supplier::where('is_active', true)
                ->orderBy('business_name')
                ->get(),

            'products' => Product::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'document_type' => ['nullable', 'string', 'max:50'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $total = collect($data['items'])->sum(function ($item) {
            return (float) $item['quantity'] * (float) $item['unit_cost'];
        });

        $purchase = Purchase::create([
            'supplier_id' => $data['supplier_id'] ?? null,
            'user_id' => Auth::id(),
            'document_type' => $data['document_type'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'purchase_date' => $data['purchase_date'],
            'total' => $total,
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['items'] as $item) {
            $product = Product::findOrFail($item['product_id']);

            $quantity = (int) $item['quantity'];
            $unitCost = (float) $item['unit_cost'];
            $subtotal = $quantity * $unitCost;

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'subtotal' => $subtotal,
            ]);

            $stockBefore = $product->stock;

            $product->update([
            'cost' => $unitCost,
            ]);

            $product->increment('stock', $quantity);
            $product->refresh();

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'type' => 'entrada',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $product->stock,
                'reason' => 'Compra #' . $purchase->id,
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
            ]);
        }

        ActivityLogger::log(
        'Compras',
        'Registró compra',
        'Compra #' . $purchase->id . ' | Total: S/ ' . $total
        );
        return back();

    }

    public function show(Purchase $purchase)
    {
    $purchase->load(['supplier', 'user', 'items.product']);

    return Inertia::render('Purchases/Show', [
        'purchase' => $purchase,
    ]);
    }
}
