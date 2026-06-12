<?php

namespace App\Http\Requests\Chatbot;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:2', 'max:1000'],
            'session_token' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Vui lòng nhập tin nhắn.',
            'message.min' => 'Tin nhắn phải có ít nhất 2 ký tự.',
            'message.max' => 'Tin nhắn không được dài quá 1000 ký tự.',
        ];
    }
}
