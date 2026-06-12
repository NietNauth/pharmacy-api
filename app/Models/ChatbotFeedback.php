<?php

namespace App\Models;

use App\Enums\ChatbotFeedbackRating;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotFeedback extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $table = 'chatbot_feedback';

    protected $fillable = [
        'message_id',
        'user_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => ChatbotFeedbackRating::class,
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatbotMessage::class, 'message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
