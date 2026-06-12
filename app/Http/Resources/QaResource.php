<?php

namespace App\Http\Resources;

use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', function () {
                if (!$this->user) return null;
                return [
                    'id' => $this->user->id,
                    'full_name' => $this->user->full_name,
                ];
            }),
            'body' => $this->body,
            'is_verified_purchase' => $this->is_verified_purchase,
            'is_visible' => (bool)$this->is_visible,
            'admin_reply' => $this->admin_reply,
            'admin_replied_at' => $this->admin_replied_at,
            'created_at' => $this->created_at,
            'product' => ($this->relationLoaded('product') && $this->product) ? new ProductResource($this->product) : null,
        ];
    }
}
