<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\CashMovement;
use App\Helpers\ActivityLogger;

class CashRegisterController extends Controller
{
    public function index()
    {
        $cashRegister = CashRegister::with(['movements.user'])
        ->where('user_id', Auth::id())
        ->where('status', 'abierta')
        ->latest()
        ->first();

        $salesQuery = Order::with(['table', 'user', 'payments'])
    ->where('status', 'pagado');

if ($cashRegister) {
    $startOfToday = now()
        ->timezone('America/Lima')
        ->startOfDay();

    $from = $cashRegister->opened_at->greaterThan($startOfToday)
        ? $cashRegister->opened_at
        : $startOfToday;

    $salesQuery->whereBetween('paid_at', [
        $from,
        now(),
    ]);
} else {
    $salesQuery->whereDate(
        'paid_at',
        now()->timezone('America/Lima')->toDateString()
    );
}

$sales = $salesQuery
    ->latest('paid_at')
    ->get()
            ->map(function ($sale) {
                $sale->paid_at_formatted = $sale->paid_at
                    ? $sale->paid_at->format('d/m/Y H:i:s')
                    : $sale->updated_at->format('d/m/Y H:i:s');

                return $sale;
            });

        $todaySales = $sales->sum('total');
        $cashIncomes = $cashRegister
        ? $cashRegister->movements->where('type', 'ingreso')->sum('amount')
        : 0;

        $cashExpenses = $cashRegister
        ? $cashRegister->movements->where('type', 'egreso')->sum('amount')
        : 0;

        $expectedCash = $cashRegister
        ? (float) $cashRegister->opening_amount + (float) $todaySales + (float) $cashIncomes - (float) $cashExpenses
        : 0;

        return Inertia::render('Caja/Index', [
            'cashRegister' => $cashRegister,
            'todaySales' => $todaySales,
            'sales' => $sales,
            'cashIncomes' => $cashIncomes,
            'cashExpenses' => $cashExpenses,
            'expectedCash' => $expectedCash,
        ]);
    }

    public function open(Request $request)
    {
    $data = $request->validate([
        'opening_amount' => ['required', 'numeric', 'min:0'],
    ]);

    $alreadyOpen = CashRegister::where('user_id', Auth::id())
        ->where('status', 'abierta')
        ->exists();

    if ($alreadyOpen) {
        return back()->withErrors([
            'cash' => 'Ya tienes una caja abierta.',
        ]);
    }

    $cashRegister = CashRegister::create([
        'user_id' => Auth::id(),
        'opening_amount' => $data['opening_amount'],
        'sales_total' => 0,
        'status' => 'abierta',
        'opened_at' => now(),
    ]);

    ActivityLogger::log(
        'Caja',
        'Abrió caja',
        'Caja #' . $cashRegister->id .
        ' | Monto inicial: S/ ' . number_format($data['opening_amount'], 2)
    );

    return back()->with('success', 'Caja abierta correctamente.');
    }

    public function close(Request $request, CashRegister $cashRegister)
    {
        $data = $request->validate([
            'closing_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $startOfToday = now()
    ->timezone('America/Lima')
    ->startOfDay();

$from = $cashRegister->opened_at->greaterThan($startOfToday)
    ? $cashRegister->opened_at
    : $startOfToday;

$to = $cashRegister->closed_at ?? now();

$salesTotal = Order::where('status', 'pagado')
    ->whereBetween('paid_at', [
        $from,
        $to,
    ])
    ->sum('total');

        $cashIncomes = $cashRegister->movements()
        ->where('type', 'ingreso')
        ->sum('amount');

        $cashExpenses = $cashRegister->movements()
        ->where('type', 'egreso')
        ->sum('amount');

        $expectedCash = (float) $cashRegister->opening_amount
        + (float) $salesTotal
        + (float) $cashIncomes
        - (float) $cashExpenses;

        $cashRegister->update([
        'closing_amount' => $data['closing_amount'],
        'sales_total' => $salesTotal,
        'expected_amount' => $expectedCash,
        'difference_amount' => (float) $data['closing_amount'] - (float) $expectedCash,
        'status' => 'cerrada',
        'closed_at' => now(),

        ]);
        ActivityLogger::log(
        'Caja',
        'Cerró caja',
        'Caja #' . $cashRegister->id . ' | Esperado: S/ ' . $expectedCash . ' | Contado: S/ ' . $data['closing_amount']
        );

        return back();
    }
}
