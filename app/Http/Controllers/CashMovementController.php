<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashMovementController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'cash_register_id' => ['required', 'exists:cash_registers,id'],
            'type' => ['required', 'in:ingreso,egreso'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        CashMovement::create([
            'cash_register_id' => $data['cash_register_id'],
            'user_id' => Auth::id(),
            'type' => $data['type'],
            'amount' => $data['amount'],
            'reason' => $data['reason'],
        ]);

        return back()->with('success', 'Movimiento registrado correctamente.');
    }
}
