<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Inertia\Inertia;

class CommandController extends Controller
{
    public function index()
    {
        $orders = Order::with([
            'table',
            'items.modifiers',
        ])
        ->where('status', 'abierto')
        ->whereHas('items', function ($q) {
            $q->where('status', '!=', 'entregado');
        })
        ->latest()
        ->get();

        return Inertia::render('Comandas/Index', [
            'orders' => $orders,
        ]);
    }

    public function preparing(OrderItem $item)
{
    $item->update([
        'status' => 'preparando',
        'preparing_at' => now(),
    ]);

    return back();
}

public function ready(OrderItem $item)
{
    $item->update([
        'status' => 'listo',
        'ready_at' => now(),
    ]);

    return back();
}

public function delivered(OrderItem $item)
{
    $item->update([
        'status' => 'entregado',
        'delivered_at' => now(),
    ]);

    $order = $item->order;

    $pendingItems = $order->items()
        ->where('status', '!=', 'entregado')
        ->count();

    $order->update([
        'ready' => $pendingItems === 0,
    ]);

    return back();
}
}
