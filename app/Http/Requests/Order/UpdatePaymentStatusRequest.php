<?php

namespace App\Http\Requests\Order;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(PaymentStatus::class)],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
