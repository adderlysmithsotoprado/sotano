<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Modifier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        return Inertia::render('Productos/Index', [
            'products' => Product::with([
                'category',
                'modifiers',
            ])
                ->latest()
                ->get(),

            'categories' => Category::where(
                'is_active',
                true
            )
                ->orderBy('name')
                ->get(),

            'modifiers' => Modifier::where(
                'is_active',
                true
            )
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'cost' => [
                'required',
                'numeric',
                'min:0',
            ],
            'stock' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'printer_area' => [
                'required',
                'in:none,cocina,bar',
            ],
            'is_active' => [
                'boolean',
            ],
            'is_favorite' => [
                'boolean',
            ],
            'modifier_ids' => [
                'nullable',
                'array',
            ],
            'modifier_ids.*' => [
                'exists:modifiers,id',
            ],
        ]);

        $modifierIds = $data['modifier_ids'] ?? [];

        unset($data['modifier_ids']);

        $data['stock'] = $data['stock'] ?? 0;
        $data['is_active'] = $request->boolean(
            'is_active'
        );
        $data['is_favorite'] = $request->boolean(
            'is_favorite'
        );

        $product = Product::create($data);

        $product->modifiers()->sync($modifierIds);

        Cache::forget('pos_products');

        return back()->with(
            'success',
            'Producto registrado correctamente.'
        );
    }

    public function update(
        Request $request,
        Product $product
    ) {
        $data = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'cost' => [
                'required',
                'numeric',
                'min:0',
            ],
            'stock' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'printer_area' => [
                'required',
                'in:none,cocina,bar',
            ],
            'is_active' => [
                'boolean',
            ],
            'is_favorite' => [
                'boolean',
            ],
            'modifier_ids' => [
                'nullable',
                'array',
            ],
            'modifier_ids.*' => [
                'exists:modifiers,id',
            ],
        ]);

        $modifierIds = $data['modifier_ids'] ?? [];

        unset($data['modifier_ids']);

        $data['stock'] = $data['stock'] ?? 0;
        $data['is_active'] = $request->boolean(
            'is_active'
        );
        $data['is_favorite'] = $request->boolean(
            'is_favorite'
        );

        $product->update($data);

        $product->modifiers()->sync($modifierIds);

        Cache::forget('pos_products');

        return back()->with(
            'success',
            'Producto actualizado correctamente.'
        );
    }

    public function destroy(Product $product)
    {
        $product->modifiers()->detach();

        $product->delete();

        Cache::forget('pos_products');

        return back()->with(
            'success',
            'Producto eliminado correctamente.'
        );
    }
}
