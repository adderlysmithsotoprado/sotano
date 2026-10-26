<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;

class StockController extends Controller
{
    public function index()
    {
        return Inertia::render('Stock/Index', [
            'products' => Product::with('category')
                ->where('stock', '<=', 5)
                ->orderBy('stock')
                ->get(),
        ]);
    }
}
