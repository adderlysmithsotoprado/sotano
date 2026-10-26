<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PromotionController extends Controller
{
    public function index()
    {
        return Inertia::render('Promociones/Index', [
            'promotions' => Promotion::with('product')
                ->latest()
                ->get(),

            'products' => Product::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePromotion($request);

        Promotion::create($this->normalizePromotionData($data, $request));

        return back()->with(
            'success',
            'Promoción registrada correctamente.'
        );
    }

    public function update(Request $request, Promotion $promotion)
    {
        $data = $this->validatePromotion($request);

        $promotion->update(
            $this->normalizePromotionData($data, $request)
        );

        return back()->with(
            'success',
            'Promoción actualizada correctamente.'
        );
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();

        return back()->with(
            'success',
            'Promoción eliminada correctamente.'
        );
    }

    private function validatePromotion(Request $request): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'type' => [
                'required',
                Rule::in([
                    '2x1',
                    'quantity_price',
                    'percentage',
                    'fixed_discount',
                ]),
            ],

            'product_id' => [
                'required',
                'exists:products,id',
            ],

            'minimum_quantity' => [
                'nullable',
                'integer',
                'min:2',
                'required_if:type,quantity_price',
            ],

            'promotional_unit_price' => [
                'nullable',
                'numeric',
                'min:0',
                'required_if:type,quantity_price',
            ],

            'discount_percentage' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:100',
                'required_if:type,percentage',
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0.01',
                'required_if:type,fixed_discount',
            ],

            'days' => [
                'nullable',
                'array',
            ],

            'days.*' => [
                'string',
            ],

            'start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'end_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'is_active' => [
                'boolean',
            ],
        ]);
    }

    private function normalizePromotionData(
        array $data,
        Request $request
    ): array {
        $data['is_active'] = $request->boolean('is_active');
        $data['days'] = $data['days'] ?? [];

        if ($data['type'] !== 'quantity_price') {
            $data['minimum_quantity'] = null;
            $data['promotional_unit_price'] = null;
        }

        if ($data['type'] !== 'percentage') {
            $data['discount_percentage'] = null;
        }

        if ($data['type'] !== 'fixed_discount') {
            $data['discount_amount'] = null;
        }

        return $data;
    }
}
