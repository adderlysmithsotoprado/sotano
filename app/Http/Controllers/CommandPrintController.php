<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;
use App\Models\BusinessSetting;

class CommandPrintController extends Controller
{
    public function show(Order $order, string $area)
    {
        if (!in_array($area, ['cocina', 'bar'])) {
            abort(404);
        }

        $order->load(['items.modifiers', 'table']);

        $items = $order->items
            ->where('printer_area', $area)
            ->where('command_sent', true)
            ->whereNull('printed_at')
            ->values();

        $order->items()
            ->where('printer_area', $area)
            ->where('command_sent', true)
            ->whereNull('printed_at')
            ->update([
                'printed_at' => now(),
            ]);

        return Inertia::render('Comandas/Print', [
        'order' => $order,
        'area' => $area,
        'items' => $items,
        'business' => BusinessSetting::first(),
        ]);
    }
}
