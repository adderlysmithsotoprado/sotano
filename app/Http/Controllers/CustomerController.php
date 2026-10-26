<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        return Inertia::render('Clientes/Index', [
            'customers' => Customer::latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'document' => ['nullable', 'string', 'max:20'],
        'phone' => ['nullable', 'string', 'max:20'],
        'email' => ['nullable', 'email'],
        'birthday' => ['nullable', 'date'],
        'is_active' => ['boolean'],
    ]);

    $data['points'] = 0;
    $data['total_spent'] = 0;
    $data['is_active'] = $request->boolean('is_active');

    Customer::create($data);

Cache::forget('pos_customers');

return back()->with(
    'success',
    'Cliente registrado correctamente.'
);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'birthday' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $customer->update($data);

Cache::forget('pos_customers');

return back()->with(
    'success',
    'Cliente actualizado correctamente.'
);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

Cache::forget('pos_customers');

return back()->with(
    'success',
    'Cliente eliminado correctamente.'
);
    }
}
