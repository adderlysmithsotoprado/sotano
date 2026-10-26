<?php

namespace App\Http\Controllers;

use App\Models\Modifier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;

class ModifierController extends Controller
{
    public function index()
{
    return Inertia::render('Modificadores/Index', [
        'modifiers' => Modifier::with('products')->latest()->get(),
        'products' => Product::orderBy('name')->get(),
    ]);
}

    public function store(Request $request)
{
    $data = $request->validate([
        'name' => ['required', 'string', 'max:100'],
        'extra_price' => ['nullable', 'numeric', 'min:0'],
        'is_active' => ['boolean'],
        'product_ids' => ['array'],
        'product_ids.*' => ['exists:products,id'],
    ]);

    $modifier = Modifier::create([
        'name' => $data['name'],
        'extra_price' => $data['extra_price'] ?? 0,
        'is_active' => $request->boolean('is_active'),
    ]);

    $modifier->products()->sync(
        $data['product_ids'] ?? []
    );

    return back();
}

    public function update(Request $request, Modifier $modifier)
{
    $data = $request->validate([
        'name' => ['required', 'string', 'max:100'],
        'extra_price' => ['nullable', 'numeric', 'min:0'],
        'is_active' => ['boolean'],
        'product_ids' => ['array'],
        'product_ids.*' => ['exists:products,id'],
    ]);

    $modifier->update([
        'name' => $data['name'],
        'extra_price' => $data['extra_price'] ?? 0,
        'is_active' => $request->boolean('is_active'),
    ]);

    $modifier->products()->sync(
        $data['product_ids'] ?? []
    );

    return back();
}

    public function destroy(Modifier $modifier)
    {
        $modifier->delete();

        return back();
    }
}
