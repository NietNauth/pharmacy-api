<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductDetailResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $additionalData = [
            'usage' => $this->usage,
            'notes' => $this->notes,
            'active_ingredient' => $this->active_ingredient,
            'manufacturer' => $this->manufacturer,
            'attributes' => $this->whenLoaded('attributes', function () {
                return $this->attributes->map(function ($attr) {
                    return [
                        'key' => $attr->attr_key,
                        'value' => $attr->attr_value,
                    ];
                });
            }),
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'url' => $image->url,
                        'is_primary' => $image->is_primary,
                        'sort_order' => $image->sort_order,
                    ];
                })->sortBy('sort_order')->values();
            }),
            'total_stock' => $this->whenLoaded('inventory', function () {
                return $this->inventory->sum('quantity_available');
            }),
            'qas' => QaResource::collection($this->whenLoaded('qas')),
        ];

        return array_merge($data, $additionalData);
    }


}
