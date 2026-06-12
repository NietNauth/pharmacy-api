<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductQa extends Model
{
    use HasUuids;

    protected $table = 'product_qas';

    const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'user_id',
        'body',
        'is_verified_purchase',
        'is_visible',
        'admin_reply',
        'admin_replied_at',
    ];

    protected $casts = [
        'is_verified_purchase' => 'boolean',
        'is_visible' => 'boolean',
        'admin_replied_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
