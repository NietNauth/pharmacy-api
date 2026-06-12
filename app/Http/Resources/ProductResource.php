<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'requires_prescription' => $this->requires_prescription,
            'status' => $this->status,
            'current_price' => $this->current_price,
            'sale_price' => $this->sale_price,
            'base_price' => $this->base_price,
            'unit' => $this->unit,
            'dosage_form' => $this->dosage_form,
            'avg_rating' => $this->avg_rating,
            'review_count' => $this->review_count,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                ];
            }),
            'primary_image' => $this->whenLoaded('images', function () {
                $primary = $this->images->firstWhere('is_primary', true) ?? $this->images->first();
                return $primary ? $primary->url : null;
            }),
        ];
    }
}
