<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalesHistoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $date = $request->input('date');

        $sales = Order::with(['table', 'user', 'items', 'payments', 'customer'])
            ->where('status', 'pagado')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', $search)
                        ->orWhereHas('table', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('customer', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('document', 'like', "%{$search}%");
                        });
                });
            })
            ->when($date, function ($query) use ($date) {
                $query->whereDate('paid_at', $date);
            })
            ->latest('paid_at')
            ->get()
            ->map(function ($sale) {
                $sale->paid_at_formatted = $sale->paid_at
                    ? $sale->paid_at->format('d/m/Y H:i:s')
                    : null;

                return $sale;
            });

        return Inertia::render('Ventas/Historial', [
            'sales' => $sales,
            'filters' => [
                'search' => $search,
                'date' => $date,
            ],
            'total' => $sales->sum('total'),
        ]);
    }
    public function export(Request $request)
{
    $date = $request->input('date');

    $sales = Order::with([
        'table',
        'user',
        'items',
        'payments',
        'customer',
    ])
        ->where('status', 'pagado')
        ->when($date, function ($query) use ($date) {
            $query->whereDate('paid_at', $date);
        })
        ->orderBy('paid_at')
        ->get();

    $fileName = $date
        ? 'ventas_' . $date . '.csv'
        : 'historial_completo_ventas.csv';

    return response()->streamDownload(function () use ($sales) {
        $output = fopen('php://output', 'w');

        // BOM UTF-8 para que Excel reconozca tildes y la ñ.
        fwrite($output, "\xEF\xBB\xBF");

        // Excel en configuración regional española suele reconocer mejor ;
        fputcsv($output, [
            'Pedido',
            'Fecha',
            'Hora',
            'Mesa',
            'Cliente',
            'Cajero',
            'Métodos de pago',
            'Venta cocina',
            'Venta bar',
            'Total',
            'Utilidad',
            'Estado',
        ], ';');

        foreach ($sales as $sale) {
            $kitchenSales = $sale->items
                ->where('printer_area', 'cocina')
                ->sum(fn ($item) => (float) $item->subtotal);

            $barSales = $sale->items
                ->where('printer_area', 'bar')
                ->sum(fn ($item) => (float) $item->subtotal);

            $profit = $sale->items
                ->sum(fn ($item) => (float) $item->profit);

            $paymentMethods = $sale->payments
                ->map(function ($payment) {
                    return mb_strtoupper($payment->method)
                        . ': S/ '
                        . number_format((float) $payment->amount, 2, '.', '');
                })
                ->implode(' | ');

            fputcsv($output, [
                $sale->id,
                $sale->paid_at?->format('d/m/Y'),
                $sale->paid_at?->format('H:i:s'),
                $sale->table?->name ?? 'Sin mesa',
                $sale->customer?->name ?? 'Cliente general',
                $sale->user?->name ?? 'Sin usuario',
                $paymentMethods,
                number_format($kitchenSales, 2, '.', ''),
                number_format($barSales, 2, '.', ''),
                number_format((float) $sale->total, 2, '.', ''),
                number_format($profit, 2, '.', ''),
                ucfirst($sale->status),
            ], ';');
        }

        // Fila final con totales.
        fputcsv($output, [], ';');

        fputcsv($output, [
            '',
            '',
            '',
            '',
            '',
            '',
            'TOTALES',
            number_format(
                $sales->sum(fn ($sale) =>
                    $sale->items
                        ->where('printer_area', 'cocina')
                        ->sum(fn ($item) => (float) $item->subtotal)
                ),
                2,
                '.',
                ''
            ),
            number_format(
                $sales->sum(fn ($sale) =>
                    $sale->items
                        ->where('printer_area', 'bar')
                        ->sum(fn ($item) => (float) $item->subtotal)
                ),
                2,
                '.',
                ''
            ),
            number_format(
                $sales->sum(fn ($sale) => (float) $sale->total),
                2,
                '.',
                ''
            ),
            number_format(
                $sales->sum(fn ($sale) =>
                    $sale->items->sum(
                        fn ($item) => (float) $item->profit
                    )
                ),
                2,
                '.',
                ''
            ),
            '',
        ], ';');

        fclose($output);
    }, $fileName, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Cache-Control' => 'no-store, no-cache',
    ]);
}

    public function show(Order $order)
    {
        $order->load(['table', 'user', 'items', 'payments', 'customer']);

        return Inertia::render('Ventas/Show', [
            'order' => $order,
        ]);
    }
}
