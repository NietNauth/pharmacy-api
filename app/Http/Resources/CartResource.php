<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items', function () {
            return $this->items->map(function ($item) {
                $product = $item->product;
                $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                $currentPrice = $product->sale_price ?? $product->base_price;
                
                return [
                    'id' => $item->id,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'current_price' => $currentPrice,
                        'primary_image' => $primaryImage ? $primaryImage->url : null,
                        'requires_prescription' => $product->requires_prescription,
                        'status' => $product->status,
                    ],
                    'quantity' => $item->quantity,
                    'price_snapshot' => $item->price_snapshot,
                    'price_changed' => (float)$item->price_snapshot !== (float)$currentPrice,
                    'prescription_id' => $item->prescription_id,
                ];
            });
        }, []);

        $itemsCollection = collect($items);

        return [
            'id' => $this->id,
            'updated_at' => $this->updated_at,
            'items' => $items,
            'summary' => [
                'items_count' => $itemsCollection->sum('quantity'),
                'subtotal' => $itemsCollection->sum(function ($item) {
                    return $item['quantity'] * $item['product']['current_price'];
                }),
            ]
        ];
    }
}
