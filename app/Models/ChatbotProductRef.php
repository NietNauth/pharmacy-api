<?php

namespace App\Models;

use App\Enums\ChatbotRefType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotProductRef extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'message_id',
        'product_id',
        'ref_type',
    ];

    protected $casts = [
        'ref_type' => ChatbotRefType::class,
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatbotMessage::class, 'message_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
