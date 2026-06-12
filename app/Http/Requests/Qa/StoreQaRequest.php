<?php

namespace App\Http\Requests\Qa;

use Illuminate\Foundation\Http\FormRequest;

class StoreQaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Vui lòng nhập nội dung câu hỏi.',
            'body.max' => 'Nội dung không được vượt quá 2000 ký tự.',
        ];
    }
}
