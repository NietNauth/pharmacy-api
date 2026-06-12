<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                    'dosage_form' => $this->product->dosage_form,
                    'requires_prescription' => $this->product->requires_prescription,
                ];
            }),
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                    'city' => $this->branch->city,
                ];
            }),
            'quantity_available' => $this->quantity_available,
            'quantity_reserved' => $this->quantity_reserved,
            'quantity_minimum' => $this->quantity_minimum,
            'expiry_date' => $this->expiry_date,
            'is_low_stock' => $this->quantity_available <= $this->quantity_minimum,
            'updated_at' => $this->updated_at,
        ];
    }
}
