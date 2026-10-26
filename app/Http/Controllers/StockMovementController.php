<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $productId = $request->input('product_id');
        $reportType = $request->input('report_type', 'kardex');
        $from = $request->input('from');
        $to = $request->input('to');

        $movements = StockMovement::with(['product', 'user'])
            ->when($productId, function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->when($from, function ($query) use ($from) {
                $query->where('created_at', '>=', Carbon::parse($from));
            })
            ->when($to, function ($query) use ($to) {
                $query->where('created_at', '<=', Carbon::parse($to));
            })
            ->latest()
            ->get()
            ->map(function ($movement) {
                $movement->created_at_formatted = $movement->created_at
                    ? $movement->created_at->format('d/m/Y H:i:s')
                    : null;

                return $movement;
            });

        return Inertia::render('Stock/Kardex', [
            'movements' => $movements,
            'products' => Product::orderBy('name')->get(),
            'filters' => [
                'product_id' => $productId,
                'report_type' => $reportType,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', 'in:entrada,ajuste,merma'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $stockBefore = $product->stock;

        if ($data['type'] === 'merma') {
            $product->decrement('stock', $data['quantity']);
        } else {
            $product->increment('stock', $data['quantity']);
        }

        $product->refresh();

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'type' => $data['type'],
            'quantity' => $data['type'] === 'merma'
                ? -$data['quantity']
                : $data['quantity'],
            'stock_before' => $stockBefore,
            'stock_after' => $product->stock,
            'reason' => $data['reason'],
        ]);

        return back();
    }

    public function export(Request $request)
    {
        $data = $request->validate([
            'report_type' => [
                'required',
                'in:kardex,ventas,eliminados_cocina,eliminados_bar,eliminados_todos,merma,ingresos,promociones',
            ],
            'product_id' => ['nullable', 'exists:products,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $reportType = $data['report_type'];
        $productId = $data['product_id'] ?? null;
        $from = !empty($data['from'])
            ? Carbon::parse($data['from'])
            : null;
        $to = !empty($data['to'])
            ? Carbon::parse($data['to'])
            : null;

        $filename = $reportType . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(
            function () use (
                $reportType,
                $productId,
                $from,
                $to
            ) {
                $file = fopen('php://output', 'w');

                // BOM para tildes y ñ en Excel.
                fwrite($file, "\xEF\xBB\xBF");

                $this->writeReportHeader(
                    $file,
                    $reportType,
                    $from,
                    $to
                );

                match ($reportType) {
                    'ventas' => $this->writeSalesReport(
                        $file,
                        $from,
                        $to
                    ),

                    'eliminados_cocina' => $this->writeDeletedReport(
                        $file,
                        'cocina',
                        $from,
                        $to
                    ),

                    'eliminados_bar' => $this->writeDeletedReport(
                        $file,
                        'bar',
                        $from,
                        $to
                    ),

                    'eliminados_todos' => $this->writeDeletedReport(
                        $file,
                        null,
                        $from,
                        $to
                    ),

                    'merma' => $this->writeStockReport(
                        $file,
                        ['merma'],
                        $productId,
                        $from,
                        $to
                    ),

                    'ingresos' => $this->writeStockReport(
                        $file,
                        ['entrada'],
                        $productId,
                        $from,
                        $to
                    ),

                    'promociones' => $this->writePromotionsReport(
                        $file,
                        $from,
                        $to
                    ),

                    default => $this->writeStockReport(
                        $file,
                        null,
                        $productId,
                        $from,
                        $to
                    ),
                };

                fclose($file);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache',
            ]
        );
    }

    private function writeReportHeader(
        $file,
        string $reportType,
        ?Carbon $from,
        ?Carbon $to
    ): void {
        $titles = [
            'kardex' => 'KARDEX DE INVENTARIO',
            'ventas' => 'VENTAS',
            'eliminados_cocina' => 'PEDIDOS ELIMINADOS - COCINA',
            'eliminados_bar' => 'PEDIDOS ELIMINADOS - BAR',
            'eliminados_todos' => 'PEDIDOS ELIMINADOS - COCINA Y BAR',
            'merma' => 'MERMA',
            'ingresos' => 'INGRESO DE PRODUCTOS',
            'promociones' => 'PROMOCIONES',
        ];

        fputcsv($file, ['SOTANO RESTOBAR'], ';');
        fputcsv($file, [$titles[$reportType] ?? 'REPORTE'], ';');

        fputcsv($file, [
            'Desde',
            $from?->format('d/m/Y H:i:s') ?? 'Inicio',
            'Hasta',
            $to?->format('d/m/Y H:i:s') ?? 'Actualidad',
            'Emisión',
            now()->format('d/m/Y H:i:s'),
        ], ';');

        fputcsv($file, [], ';');
    }

    private function writeSalesReport(
        $file,
        ?Carbon $from,
        ?Carbon $to
    ): void {
        $orders = Order::with([
            'table',
            'user',
            'customer',
            'items',
            'payments',
        ])
            ->where('status', 'pagado')
            ->when($from, fn ($query) =>
                $query->where('paid_at', '>=', $from)
            )
            ->when($to, fn ($query) =>
                $query->where('paid_at', '<=', $to)
            )
            ->orderBy('paid_at')
            ->get();

        fputcsv($file, [
            'Pedido',
            'Mesa',
            'Fecha y hora',
            'Código',
            'Descripción',
            'Área',
            'Cantidad',
            'Precio unitario',
            'Subtotal',
            'Cajero',
            'Cliente',
        ], ';');

        $grandTotal = 0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                fputcsv($file, [
                    $order->id,
                    $order->table?->name ?? 'Sin mesa',
                    $order->paid_at?->format('d/m/Y H:i:s'),
                    $item->product_id ?? '',
                    $item->product_name,
                    strtoupper($item->printer_area ?? ''),
                    $item->quantity,
                    number_format((float) $item->unit_price, 2, '.', ''),
                    number_format((float) $item->subtotal, 2, '.', ''),
                    $order->user?->name ?? 'Sin usuario',
                    $order->customer?->name ?? 'Cliente general',
                ], ';');

                $grandTotal += (float) $item->subtotal;
            }
        }

        fputcsv($file, [], ';');
        fputcsv($file, [
            '',
            '',
            '',
            '',
            'TOTAL VENTAS',
            '',
            '',
            '',
            number_format($grandTotal, 2, '.', ''),
        ], ';');
    }

    private function writeDeletedReport(
        $file,
        ?string $area,
        ?Carbon $from,
        ?Carbon $to
    ): void {
        /*
         * Por ahora este reporte usa pedidos cancelados conservados
         * en la base de datos.
         *
         * Los productos eliminados individualmente no pueden
         * recuperarse si fueron borrados físicamente de order_items.
         */

        $orders = Order::with([
            'table',
            'user',
            'items',
        ])
            ->where('status', 'cancelado')
            ->when($from, fn ($query) =>
                $query->where('cancelled_at', '>=', $from)
            )
            ->when($to, fn ($query) =>
                $query->where('cancelled_at', '<=', $to)
            )
            ->orderBy('cancelled_at')
            ->get();

        fputcsv($file, [
            'Número',
            'Mesa',
            'Fecha y hora',
            'Código',
            'Descripción',
            'Área',
            'Cantidad',
            'Subtotal',
            'Motivo',
            'Usuario',
        ], ';');

        $rowNumber = 1;
        $total = 0;

        foreach ($orders as $order) {
            $items = $area
                ? $order->items->where('printer_area', $area)
                : $order->items;

            foreach ($items as $item) {
                fputcsv($file, [
                    $rowNumber++,
                    $order->table?->name ?? 'Sin mesa',
                    $order->cancelled_at?->format('d/m/Y H:i:s'),
                    $item->product_id ?? '',
                    $item->product_name,
                    strtoupper($item->printer_area ?? ''),
                    $item->quantity,
                    number_format((float) $item->subtotal, 2, '.', ''),
                    $order->cancel_reason ?? 'Sin motivo',
                    $order->user?->name ?? 'Sistema',
                ], ';');

                $total += (float) $item->subtotal;
            }
        }

        fputcsv($file, [], ';');
        fputcsv($file, [
            '',
            '',
            '',
            '',
            'TOTAL PEDIDOS ELIMINADOS',
            '',
            '',
            number_format($total, 2, '.', ''),
        ], ';');
    }

    private function writeStockReport(
        $file,
        ?array $types,
        ?int $productId,
        ?Carbon $from,
        ?Carbon $to
    ): void {
        $movements = StockMovement::with([
            'product',
            'user',
        ])
            ->when($types, fn ($query) =>
                $query->whereIn('type', $types)
            )
            ->when($productId, fn ($query) =>
                $query->where('product_id', $productId)
            )
            ->when($from, fn ($query) =>
                $query->where('created_at', '>=', $from)
            )
            ->when($to, fn ($query) =>
                $query->where('created_at', '<=', $to)
            )
            ->orderBy('created_at')
            ->get();

        fputcsv($file, [
            'Número',
            'Fecha y hora',
            'Código',
            'Producto',
            'Usuario',
            'Tipo',
            'Cantidad',
            'Stock antes',
            'Stock después',
            'Motivo',
        ], ';');

        $totalQuantity = 0;

        foreach ($movements as $index => $movement) {
            fputcsv($file, [
                $index + 1,
                $movement->created_at?->format('d/m/Y H:i:s'),
                $movement->product_id,
                $movement->product?->name ?? 'Producto eliminado',
                $movement->user?->name ?? 'Sistema',
                strtoupper($movement->type),
                $movement->quantity,
                $movement->stock_before,
                $movement->stock_after,
                $movement->reason ?? '',
            ], ';');

            $totalQuantity += abs((int) $movement->quantity);
        }

        fputcsv($file, [], ';');
        fputcsv($file, [
            '',
            '',
            '',
            '',
            '',
            'TOTAL CANTIDAD',
            $totalQuantity,
        ], ';');
    }

    private function writePromotionsReport(
        $file,
        ?Carbon $from,
        ?Carbon $to
    ): void {
        $items = OrderItem::with([
            'order.table',
            'order.user',
        ])
            ->where(function ($query) {
                $query->where('discount', '>', 0)
                    ->orWhereNotNull('promotion_name');
            })
            ->whereHas('order', function ($query) use ($from, $to) {
                $query->where('status', 'pagado')
                    ->when($from, fn ($subQuery) =>
                        $subQuery->where('paid_at', '>=', $from)
                    )
                    ->when($to, fn ($subQuery) =>
                        $subQuery->where('paid_at', '<=', $to)
                    );
            })
            ->get();

        fputcsv($file, [
            'Pedido',
            'Mesa',
            'Fecha y hora',
            'Producto',
            'Promoción',
            'Cantidad',
            'Subtotal',
            'Descuento',
            'Cajero',
        ], ';');

        $totalDiscount = 0;

        foreach ($items as $item) {
            fputcsv($file, [
                $item->order_id,
                $item->order?->table?->name ?? 'Sin mesa',
                $item->order?->paid_at?->format('d/m/Y H:i:s'),
                $item->product_name,
                $item->promotion_name ?? 'Promoción',
                $item->quantity,
                number_format((float) $item->subtotal, 2, '.', ''),
                number_format((float) $item->discount, 2, '.', ''),
                $item->order?->user?->name ?? 'Sin usuario',
            ], ';');

            $totalDiscount += (float) $item->discount;
        }

        fputcsv($file, [], ';');
        fputcsv($file, [
            '',
            '',
            '',
            '',
            'TOTAL DESCUENTOS',
            '',
            '',
            number_format($totalDiscount, 2, '.', ''),
        ], ';');
    }
}
