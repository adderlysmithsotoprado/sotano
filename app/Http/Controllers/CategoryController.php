<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Categorias/Index', [
            'categories' => Category::latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:comida,bebida,otro'],
            'is_active' => ['boolean'],
        ]);

        Category::create($data);

        Cache::forget('pos_categories');

        return back();
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:comida,bebida,otro'],
            'is_active' => ['boolean'],
        ]);

        $category->update($data);

        Cache::forget('pos_categories');

        return back();
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors([
                'category' => 'No puedes eliminar una categoría que tiene productos.',
            ]);
        }

        $category->delete();

        Cache::forget('pos_categories');

        return back();
    }
}
