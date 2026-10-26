<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\OrderItem;

class DashboardController extends Controller
{
    public function index()
{
    $cashRegister = CashRegister::where('user_id', Auth::id())
        ->where('status', 'abierta')
        ->latest('opened_at')
        ->first();

    $openOrderTableIds = Order::where('status', 'abierto')
        ->pluck('restaurant_table_id')
        ->filter()
        ->unique();

    $occupiedTables = $openOrderTableIds->count();

    $totalTables = RestaurantTable::where('is_active', true)
        ->count();

    $freeTables = max(0, $totalTables - $occupiedTables);

    /*
     * Las ventas y la utilidad pertenecen al turno de caja abierto.
     * Si no hay caja abierta, ambos indicadores quedan en cero.
     */
   $todaySales = 0;
$kitchenSales = 0;
$barSales = 0;
$todayProfit = 0;

if ($cashRegister) {
    $from = $cashRegister->opened_at;
    $to = now();

    $paidOrderIds = Order::query()
        ->where('status', 'pagado')
        ->whereBetween('paid_at', [$from, $to])
        ->pluck('id');

    $todaySales = Order::query()
        ->whereIn('id', $paidOrderIds)
        ->sum('total');

    $salesByArea = DB::table('order_items')
        ->leftJoin(
            'products',
            'products.id',
            '=',
            'order_items.product_id'
        )
        ->whereIn('order_items.order_id', $paidOrderIds)
        ->where(function ($query) {
            $query->whereNull('order_items.status')
                ->orWhere('order_items.status', '!=', 'cancelado');
        })
        ->selectRaw("
            COALESCE(SUM(
                CASE
                    WHEN LOWER(TRIM(
                        CASE
                            WHEN order_items.printer_area IS NULL
                                OR TRIM(order_items.printer_area) = ''
                                OR LOWER(TRIM(order_items.printer_area)) = 'none'
                            THEN products.printer_area
                            ELSE order_items.printer_area
                        END
                    )) = 'cocina'
                    THEN order_items.subtotal
                    ELSE 0
                END
            ), 0) AS kitchen_sales,

            COALESCE(SUM(
                CASE
                    WHEN LOWER(TRIM(
                        CASE
                            WHEN order_items.printer_area IS NULL
                                OR TRIM(order_items.printer_area) = ''
                                OR LOWER(TRIM(order_items.printer_area)) = 'none'
                            THEN products.printer_area
                            ELSE order_items.printer_area
                        END
                    )) = 'bar'
                    THEN order_items.subtotal
                    ELSE 0
                END
            ), 0) AS bar_sales,

            COALESCE(SUM(order_items.profit), 0) AS total_profit
        ")
        ->first();

    $kitchenSales = (float) ($salesByArea->kitchen_sales ?? 0);
    $barSales = (float) ($salesByArea->bar_sales ?? 0);
    $todayProfit = (float) ($salesByArea->total_profit ?? 0);
}


    return Inertia::render('Dashboard', [
    'todaySales' => (float) $todaySales,
    'todayKitchenSales' => (float) $kitchenSales,
    'todayBarSales' => (float) $barSales,
    'todayProfit' => (float) $todayProfit,

    'occupiedTables' => $occupiedTables,
    'freeTables' => $freeTables,
    'productsCount' => Product::count(),

    'lowStockProducts' => Product::where(
        'stock',
        '<=',
        5
    )
        ->orderBy('stock')
        ->get(),

    'cashRegister' => $cashRegister,
]);
}
}
