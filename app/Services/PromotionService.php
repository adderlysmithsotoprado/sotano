<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Promotion;
use Carbon\Carbon;

class PromotionService
{
    public function apply(Order $order): void
    {
        $order->load([
    'items' => function ($q) {
        $q->where('status', '!=', 'cancelado');
    }
]);

        $this->clearPromotions($order);

        $now = Carbon::now('America/Lima');
        $today = strtolower($now->format('l'));

        $promotions = Promotion::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        foreach ($promotions as $promotion) {
            if (!$this->isPromotionValidNow($promotion, $today, $now)) {
                continue;
            }

            $items = $order->items
    ->where('status','!=','cancelado')
    ->where('product_id',$promotion->product_id);

            foreach ($items as $item) {
                $this->applyPromotionToItem($promotion, $item);
            }
        }

        $this->recalculateTotal($order);
    }

    private function clearPromotions(Order $order): void
    {
        foreach ($order->items->where('status','!=','cancelado') as $item) {
            $baseSubtotal =
                (float) $item->unit_price *
                (int) $item->quantity;

            $item->update([
                'discount' => 0,
                'promotion_name' => null,
                'subtotal' => $baseSubtotal,
            ]);
        }
    }

    private function applyPromotionToItem(
        Promotion $promotion,
        $item
    ): void {
        $quantity = (int) $item->quantity;
        $unitPrice = (float) $item->unit_price;
        $baseSubtotal = $unitPrice * $quantity;

        if ($quantity <= 0 || $baseSubtotal <= 0) {
            return;
        }

        $discount = 0;

        switch ($promotion->type) {
            case '2x1':
                $freeUnits = intdiv($quantity, 2);

                if ($freeUnits <= 0) {
                    return;
                }

                $discount = $freeUnits * $unitPrice;
                break;

            case 'quantity_price':
    $minimumQuantity =
        (int) $promotion->minimum_quantity;

    $promotionalUnitPrice =
        (float) $promotion->promotional_unit_price;

    if (
        $minimumQuantity <= 0 ||
        $quantity < $minimumQuantity ||
        $promotionalUnitPrice < 0 ||
        $promotionalUnitPrice >= $unitPrice
    ) {
        return;
    }

    $promotionalBlocks = intdiv(
        $quantity,
        $minimumQuantity
    );

    $promotionalUnits =
        $promotionalBlocks * $minimumQuantity;

    $regularUnits =
        $quantity - $promotionalUnits;

    $promotionalSubtotal =
        $promotionalUnits * $promotionalUnitPrice;

    $regularSubtotal =
        $regularUnits * $unitPrice;

    $finalSubtotal =
        $promotionalSubtotal + $regularSubtotal;

    $discount =
        $baseSubtotal - $finalSubtotal;
    break;

            case 'percentage':
                $percentage =
                    (float) $promotion->discount_percentage;

                if ($percentage <= 0 || $percentage > 100) {
                    return;
                }

                $discount =
                    $baseSubtotal * ($percentage / 100);
                break;

            case 'fixed_discount':
                $discountAmount =
                    (float) $promotion->discount_amount;

                if ($discountAmount <= 0) {
                    return;
                }

                $discount = min(
                    $discountAmount,
                    $baseSubtotal
                );
                break;

            default:
                return;
        }

        $discount = round(max(0, $discount), 2);

        if ($discount <= 0) {
            return;
        }

        $finalSubtotal = round(
            max(0, $baseSubtotal - $discount),
            2
        );

        $item->update([
            'discount' => $discount,
            'promotion_name' => $promotion->name,
            'subtotal' => $finalSubtotal,
            'profit' => round(
                $finalSubtotal -
                ((float) $item->unit_cost * $quantity),
                2
            ),
        ]);
    }

    private function isPromotionValidNow(
        Promotion $promotion,
        string $today,
        Carbon $now
    ): bool {
        $days = $promotion->days ?? [];

        if (
            is_array($days) &&
            count($days) > 0 &&
            !in_array($today, $days, true)
        ) {
            return false;
        }

        if (
            $promotion->start_time &&
            $promotion->end_time
        ) {
            $currentTime = $now->format('H:i:s');

            $startTime = strlen($promotion->start_time) === 5
                ? $promotion->start_time . ':00'
                : $promotion->start_time;

            $endTime = strlen($promotion->end_time) === 5
                ? $promotion->end_time . ':00'
                : $promotion->end_time;

            if ($startTime <= $endTime) {
                if (
                    $currentTime < $startTime ||
                    $currentTime > $endTime
                ) {
                    return false;
                }
            } else {
                // Horarios que cruzan medianoche.
                if (
                    $currentTime < $startTime &&
                    $currentTime > $endTime
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    private function recalculateTotal(Order $order): void
    {
        $order->update([
            'total' => round(
                (float) $order->items()->sum('subtotal'),
                2
            ),
        ]);
    }
}
