<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\ChatRequest;
use App\Models\ChatbotFeedback;
use App\Services\ChatbotService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    use ApiResponse;

    protected ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    public function message(ChatRequest $request)
    {
        $result = $this->chatbotService->chat($request);
        return $this->success($result);
    }

    public function trending()
    {
        $questions = $this->chatbotService->getTrendingQuestions();
        return $this->success($questions);
    }

    public function settings()
    {
        $welcome = \App\Models\ChatbotSetting::where('key', 'chatbot_welcome_message')->first();
        $suggestions = \App\Models\ChatbotSetting::where('key', 'manual_suggestions')->first();

        return $this->success([
            'welcome_message' => $welcome ? $welcome->value : null,
            'suggestions' => $suggestions ? $suggestions->value : [],
        ]);
    }

    public function history(Request $request)
    {
        $token = $request->header('X-Session-Token');
        if (!$token) return $this->error('Missing session token', 400);

        $history = $this->chatbotService->getSessionHistory($token);
        return $this->success($history);
    }

    public function clearHistory(Request $request)
    {
        $token = $request->header('X-Session-Token');
        if (!$token) return $this->error('Missing session token', 400);

        $session = \App\Models\ChatbotSession::where('session_token', $token)->first();
        
        if ($session) {
            \App\Models\ChatbotMessage::where('session_id', $session->id)->delete();
        }

        return $this->success(null, 'Đã xóa lịch sử trò chuyện.');
    }

    public function feedback(Request $request, $messageId)
    {
        $validated = $request->validate([
            'rating' => ['required', 'in:helpful,not_helpful,inaccurate'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        ChatbotFeedback::create([
            'message_id' => $messageId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return $this->success(null, 'Cảm ơn phản hồi của bạn.');
    }
}
