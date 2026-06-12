<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_type' => ['required', 'in:pickup,standard,express'],
            'address_id' => ['nullable', 'exists:user_addresses,id'],
            'branch_id' => ['required_if:delivery_type,pickup', 'nullable', 'exists:branches,id'],
            'delivery_address' => ['exclude_if:delivery_type,pickup', 'exclude_unless:address_id,null', 'required', 'string', 'max:512'],
            'recipient_name' => ['exclude_unless:address_id,null', 'required', 'string', 'max:150'],
            'recipient_phone' => ['exclude_unless:address_id,null', 'required', 'regex:/^(0|\+84)[0-9]{8,9}$/'],
            'payment_method' => ['required', 'in:cod,momo,vnpay,zalopay'],
            'coupon_code' => ['nullable', 'string'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_type.required' => 'Phương thức giao hàng là bắt buộc.',
            'delivery_type.in' => 'Phương thức giao hàng không hợp lệ.',
            'branch_id.required_if' => 'Chi nhánh nhận hàng là bắt buộc khi chọn lấy tại cửa hàng.',
            'branch_id.exists' => 'Chi nhánh không tồn tại.',
            'delivery_address.required_unless' => 'Địa chỉ giao hàng là bắt buộc.',
            'recipient_name.required_unless' => 'Tên người nhận là bắt buộc.',
            'recipient_phone.required_unless' => 'Số điện thoại người nhận là bắt buộc.',
            'recipient_phone.regex' => 'Số điện thoại không hợp lệ.',
            'payment_method.required' => 'Phương thức thanh toán là bắt buộc.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'note.max' => 'Ghi chú không được vượt quá 500 ký tự.',
        ];
    }
}
