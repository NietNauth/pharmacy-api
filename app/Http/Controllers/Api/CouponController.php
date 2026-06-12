<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ApiResponse;

    public function check(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $code = $request->code;
        $subtotal = $request->subtotal;

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon || !$coupon->is_active) {
            return $this->error('Mã giảm giá không tồn tại hoặc đã bị khóa.', 400);
        }

        $today = now();
        if ($today < $coupon->valid_from || $today > $coupon->valid_until) {
            return $this->error('Mã giảm giá đã hết hạn hoặc chưa đến thời gian sử dụng.', 400);
        }

        if ($coupon->used_count >= $coupon->max_uses) {
            return $this->error('Mã giảm giá đã hết lượt sử dụng.', 400);
        }

        if ($subtotal < $coupon->min_order_value) {
            return $this->error("Giá trị đơn hàng tối thiểu để dùng mã này là {$coupon->min_order_value}.", 400);
        }

        $discountAmount = 0;
        $type = is_object($coupon->discount_type) ? $coupon->discount_type->value : $coupon->discount_type;

        if ($type === 'percent') {
            $calc = $subtotal * ($coupon->discount_value / 100);
            if ($coupon->max_discount) {
                $calc = min($calc, $coupon->max_discount);
            }
            $discountAmount = $calc;
        } else {
            $discountAmount = $coupon->discount_value;
        }

        return $this->success([
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'discount_amount' => $discountAmount,
            'discount_type' => $type,
        ], 'Mã giảm giá hợp lệ.');
    }

    public function index()
    {
        $coupons = Coupon::orderBy('valid_from', 'desc')->get();
        return $this->success($coupons);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'unique:coupons,code'],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', 'string', 'in:fixed,percent'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['required', 'integer', 'min:1'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after:valid_from'],
            'is_active' => ['boolean'],
        ]);

        $coupon = Coupon::create($validated);
        return $this->success($coupon, 'Đã tạo mã giảm giá mới.', 201);
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'unique:coupons,code,' . $id],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', 'string', 'in:fixed,percent'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['required', 'integer', 'min:1'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after:valid_from'],
            'is_active' => ['boolean'],
        ]);

        $coupon->update($validated);
        return $this->success($coupon, 'Đã cập nhật mã giảm giá.');
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();
        return $this->success(null, 'Đã xóa mã giảm giá.');
    }
}
