<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrinterController extends Controller
{
    public function index()
    {
        return Inertia::render('Impresoras/Index', [
            'settings' => BusinessSetting::first(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'kitchen_printer' => ['nullable', 'string', 'max:255'],
            'bar_printer' => ['nullable', 'string', 'max:255'],
            'ticket_printer' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = BusinessSetting::firstOrCreate([]);

        $settings->update($data);

        return back()->with('success', 'Impresoras actualizadas correctamente.');
    }
}
