<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_id' => $this->inventory_id,
            'actor' => [
                'id' => $this->actor->id ?? null,
                'full_name' => $this->actor->full_name ?? 'Hệ thống',
            ],
            'action_type' => is_object($this->action_type) ? $this->action_type->value : $this->action_type,
            'quantity_delta' => $this->quantity_delta,
            'quantity_after' => $this->quantity_after,
            'note' => $this->note,
            'ref_order_id' => $this->ref_order_id,
            'created_at' => $this->created_at,
        ];
    }
}
