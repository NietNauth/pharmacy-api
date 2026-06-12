<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'status' => $this->status,
            'delivery_type' => $this->delivery_type,
            'total' => $this->total,
            'items_count' => $this->items_count ?? ($this->items ? $this->items->sum('quantity') : 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => [
                'name' => $this->recipient_name,
                'phone' => $this->recipient_phone,
            ],
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                    'address' => $this->branch->address,
                ];
            }),
            'payment' => $this->whenLoaded('payment', function () {
                return [
                    'method' => $this->payment->method,
                    'status' => $this->payment->status,
                    'transaction_id' => $this->payment->transaction_id,
                    'paid_at' => $this->payment->paid_at,
                ];
            }),
        ];
    }
}
