<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;
use App\Models\BusinessSetting;

class TicketController extends Controller
{
    public function show(Order $order)
    {


    $order->load([
    'items.modifiers',
    'table',
    'user',
    'payments',
    'customer',
    ]);

    return Inertia::render('Ticket/Show', [
    'order' => $order,
    'business' => BusinessSetting::first(),
    ]);
    }
    public function markPrinted(Order $order)
    {
    $order->update([
        'ticket_printed_at' => now(),
    ]);

    return back();
    }
}
