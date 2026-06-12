<?php

namespace App\Models;

use App\Enums\InventoryAction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'inventory_id',
        'actor_id',
        'action_type',
        'quantity_delta',
        'quantity_after',
        'note',
        'ref_order_id',
    ];

    protected $casts = [
        'action_type' => InventoryAction::class,
        'quantity_delta' => 'integer',
        'quantity_after' => 'integer',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'ref_order_id');
    }
}
