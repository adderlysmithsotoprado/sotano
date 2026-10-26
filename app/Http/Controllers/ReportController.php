<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $sales = Order::where('status', 'pagado')
            ->whereBetween('paid_at', [
                $from . ' 00:00:00',
                $to . ' 23:59:59',
            ])
            ->sum('total');

        $cost = OrderItem::whereHas('order', function ($query) use ($from, $to) {
                $query->where('status', 'pagado')
                    ->whereBetween('paid_at', [
                        $from . ' 00:00:00',
                        $to . ' 23:59:59',
                    ]);
            })
            ->sum(DB::raw('unit_cost * quantity'));

        $profit = OrderItem::whereHas('order', function ($query) use ($from, $to) {
                $query->where('status', 'pagado')
                    ->whereBetween('paid_at', [
                        $from . ' 00:00:00',
                        $to . ' 23:59:59',
                    ]);
            })
            ->sum('profit');

        $topProducts = OrderItem::select(
                'product_name',
                DB::raw('SUM(quantity) as qty'),
                DB::raw('SUM(subtotal) as sales'),
                DB::raw('SUM(profit) as profit')
            )
            ->whereHas('order', function ($query) use ($from, $to) {
                $query->where('status', 'pagado')
                    ->whereBetween('paid_at', [
                        $from . ' 00:00:00',
                        $to . ' 23:59:59',
                    ]);
            })
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        $topProfitableProducts = OrderItem::select(
                'product_name',
                DB::raw('SUM(quantity) as qty'),
                DB::raw('SUM(subtotal) as sales'),
                DB::raw('SUM(profit) as profit')
            )
            ->whereHas('order', function ($query) use ($from, $to) {
                $query->where('status', 'pagado')
                    ->whereBetween('paid_at', [
                        $from . ' 00:00:00',
                        $to . ' 23:59:59',
                    ]);
            })
            ->groupBy('product_name')
            ->orderByDesc('profit')
            ->limit(10)
            ->get();

        $paidOrdersCount = Order::where('status', 'pagado')
            ->whereBetween('paid_at', [
                $from . ' 00:00:00',
                $to . ' 23:59:59',
            ])
            ->count();

        $averageTicket = $paidOrdersCount > 0
            ? round($sales / $paidOrdersCount, 2)
            : 0;

        $recentSales = Order::with(['table', 'user'])
            ->where('status', 'pagado')
            ->latest('paid_at')
            ->limit(10)
            ->get();

        return Inertia::render('Reportes/Index', [
            'from' => $from,
            'to' => $to,
            'sales' => $sales,
            'cost' => $cost,
            'profit' => $profit,
            'margin' => $sales > 0 ? round(($profit / $sales) * 100, 2) : 0,
            'paidOrdersCount' => $paidOrdersCount,
            'averageTicket' => $averageTicket,
            'topProducts' => $topProducts,
            'topProfitableProducts' => $topProfitableProducts,
            'recentSales' => $recentSales,
        ]);
    }
}
