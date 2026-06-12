<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'PHARMA10',
                'description' => 'Giảm 10% cho đơn hàng từ 200k',
                'discount_type' => 'percent',
                'discount_value' => 10,
                'min_order_value' => 200000,
                'max_discount' => 50000,
                'max_uses' => 100,
                'used_count' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'WELCOME50',
                'description' => 'Tặng 50k cho khách hàng mới (Đơn từ 300k)',
                'discount_type' => 'fixed',
                'discount_value' => 50000,
                'min_order_value' => 300000,
                'max_discount' => null,
                'max_uses' => 50,
                'used_count' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(6),
                'is_active' => true,
            ],
            [
                'code' => 'HEAL2026',
                'description' => 'Mã tri ân khách hàng - Giảm 20k',
                'discount_type' => 'fixed',
                'discount_value' => 20000,
                'min_order_value' => 100000,
                'max_discount' => null,
                'max_uses' => 200,
                'used_count' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'SUPERVIP',
                'description' => 'Ưu đãi đặc biệt giảm 20% tối đa 100k',
                'discount_type' => 'percent',
                'discount_value' => 20,
                'min_order_value' => 500000,
                'max_discount' => 100000,
                'max_uses' => 10,
                'used_count' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addMonth(),
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::create($coupon);
        }
    }
}
