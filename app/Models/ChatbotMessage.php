<?php

namespace App\Models;

use App\Enums\ChatbotRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatbotMessage extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'session_id',
        'role',
        'content',
        'intent_detected',
        'confidence_score',
        'metadata',
    ];

    protected $casts = [
        'role' => ChatbotRole::class,
        'confidence_score' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatbotSession::class, 'session_id');
    }

    public function productRefs(): HasMany
    {
        return $this->hasMany(ChatbotProductRef::class, 'message_id');
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(ChatbotFeedback::class, 'message_id');
    }
}
