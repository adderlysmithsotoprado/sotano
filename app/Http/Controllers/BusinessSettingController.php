<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class BusinessSettingController extends Controller
{
    public function index()
    {
        $settings = BusinessSetting::firstOrCreate(
            ['id' => 1],
            [
                'business_name' => 'SOTANO RESTOBAR',
                'ticket_message' => 'Gracias por su preferencia',
            ]
        );

        return Inertia::render('Configuracion/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $settings = BusinessSetting::firstOrCreate(
            ['id' => 1],
            [
                'business_name' => 'SOTANO RESTOBAR',
                'ticket_message' => 'Gracias por su preferencia',
            ]
        );

        $data = $request->validate([
        'business_name' => ['required', 'string', 'max:255'],
        'ruc' => ['nullable', 'string', 'max:20'],
        'address' => ['nullable', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:50'],
        'ticket_message' => ['nullable', 'string', 'max:255'],
        'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        'kitchen_printer' => ['nullable', 'string', 'max:255'],
        'bar_printer' => ['nullable', 'string', 'max:255'],
        'ticket_printer' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->hasFile('logo')) {

        if ($settings->logo_path) {
        Storage::disk('public')->delete($settings->logo_path);
        }

        $data['logo_path'] = $request
        ->file('logo')
        ->store('logos', 'public');
        }

        unset($data['logo']);

        $settings->update($data);

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
