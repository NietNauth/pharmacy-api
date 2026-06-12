<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ChatbotSettingController extends Controller
{
    use ApiResponse;

    public function getSuggestions()
    {
        $setting = ChatbotSetting::where('key', 'manual_suggestions')->first();
        return $this->success($setting ? $setting->value : []);
    }

    public function updateSuggestions(Request $request)
    {
        $validated = $request->validate([
            'suggestions' => ['required', 'array', 'min:0', 'max:4'],
            'suggestions.*' => ['string', 'max:255'],
        ]);

        ChatbotSetting::updateOrCreate(
            ['key' => 'manual_suggestions'],
            ['value' => $validated['suggestions']]
        );

        return $this->success(null, 'Cập nhật gợi ý chatbot thành công.');
    }

    public function getAdvancedSettings()
    {
        $prompt = ChatbotSetting::where('key', 'chatbot_system_prompt')->first();
        $stopwords = ChatbotSetting::where('key', 'chatbot_stopwords')->first();
        $welcome = ChatbotSetting::where('key', 'chatbot_welcome_message')->first();

        return $this->success([
            'system_prompt' => $prompt ? $prompt->value : null,
            'stopwords' => $stopwords ? $stopwords->value : null,
            'welcome_message' => $welcome ? $welcome->value : null,
        ]);
    }

    public function updateAdvancedSettings(Request $request)
    {
        $validated = $request->validate([
            'system_prompt' => ['nullable', 'string'],
            'welcome_message' => ['nullable', 'string'],
            'stopwords' => ['nullable', 'array'],
            'stopwords.*' => ['string'],
        ]);

        if (isset($validated['system_prompt'])) {
            ChatbotSetting::updateOrCreate(
                ['key' => 'chatbot_system_prompt'],
                ['value' => $validated['system_prompt']]
            );
        }

        if (isset($validated['welcome_message'])) {
            ChatbotSetting::updateOrCreate(
                ['key' => 'chatbot_welcome_message'],
                ['value' => $validated['welcome_message']]
            );
        }

        if (isset($validated['stopwords'])) {
            ChatbotSetting::updateOrCreate(
                ['key' => 'chatbot_stopwords'],
                ['value' => $validated['stopwords']]
            );
        }

        return $this->success(null, 'Cập nhật cấu hình nâng cao thành công.');
    }
}
