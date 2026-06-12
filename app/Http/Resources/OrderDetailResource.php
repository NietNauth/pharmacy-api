<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderDetailResource extends OrderResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $additionalData = [
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'shipping_fee' => $this->shipping_fee,
            'delivery_address' => $this->delivery_address,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'note' => $this->note,
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                    'address' => $this->branch->address,
                ];
            }),
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    $product = $item->product;
                    $primaryImage = $product ? ($product->images->firstWhere('is_primary', true) ?? $product->images->first()) : null;
                    
                    return [
                        'id' => $item->id,
                        'product' => $product ? [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'primary_image' => $primaryImage ? $primaryImage->url : null,
                        ] : null,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                        'prescription_id' => $item->prescription_id,
                    ];
                });
            }),
            'payment' => $this->whenLoaded('payment'),
            'histories' => $this->whenLoaded('histories'),
            'coupon' => $this->whenLoaded('coupon', function () {
                return [
                    'code' => $this->coupon->code,
                    'discount_type' => $this->coupon->discount_type,
                    'discount_value' => $this->coupon->discount_value,
                ];
            }),
        ];

        return array_merge($data, $additionalData);
    }
}
