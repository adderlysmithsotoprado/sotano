<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaleHistoryController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        $sales = Order::with(['table', 'user'])
            ->where('status', 'pagado')
            ->whereDate('updated_at', $date)
            ->latest('updated_at')
            ->get();

        return Inertia::render('Ventas/Historial', [
            'sales' => $sales,
            'date' => $date,
            'total' => $sales->sum('total'),
        ]);
    }
    public function export(Request $request)
    {
    $date = $request->input('date', now()->toDateString());

    $sales = Order::with(['table', 'user'])
        ->where('status', 'pagado')
        ->whereDate('updated_at', $date)
        ->latest('updated_at')
        ->get();

    $filename = "ventas_{$date}.csv";

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];

    $callback = function () use ($sales) {
        $file = fopen('php://output', 'w');

        fputcsv($file, ['Pedido', 'Mesa', 'Cajero', 'Fecha', 'Total']);

        foreach ($sales as $sale) {
            fputcsv($file, [
                '#' . $sale->id,
                $sale->table?->name ?? 'Sin mesa',
                $sale->user?->name ?? 'Sin usuario',
                $sale->updated_at,
                $sale->total,
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
    }
}
