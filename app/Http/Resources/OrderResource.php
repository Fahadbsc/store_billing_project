<?php

namespace App\Http\Resources;

use App\Services\PriceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $calc = app(PriceCalculator::class);

        return [
            'id' => $this->id,
            'customer' => [
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ],
            'items' => $this->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'name' => $i->product->name,
                'quantity' => $i->quantity,
                'unit_price' => $i->unit_price,
                'tax_percent' => $i->tax_percent,
                'line_subtotal' => $i->line_subtotal,
                'line_tax' => $i->line_tax,
                'line_total' => $i->line_total,
            ])->values(),
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            'amount_paid' => $this->amount_paid,
            'balance_returned' => $this->balance_returned,
            'change_breakdown' => $this->balance_returned === null
                ? null
                : $calc->change($calc->toPaise($this->balance_returned)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
