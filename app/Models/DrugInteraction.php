<?php

namespace App\Models;

use App\Enums\InteractionSeverity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugInteraction extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'product_a_id',
        'product_b_id',
        'severity',
        'description',
        'clinical_effect',
        'source_reference',
    ];

    protected $casts = [
        'severity' => InteractionSeverity::class,
    ];

    public function productA(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_a_id');
    }

    public function productB(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_b_id');
    }
}
