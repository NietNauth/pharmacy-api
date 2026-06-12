<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Unconfirmed = 'unconfirmed';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Chờ duyệt',
            self::Unconfirmed => 'Chưa xác nhận',
            self::Confirmed => 'Đã xác nhận',
            self::Processing => 'Đang xử lý',
            self::Shipped => 'Đang giao hàng',
            self::Delivered => 'Đã giao hàng',
            self::Cancelled => 'Đã hủy',
            self::Refunded => 'Đã hoàn tiền',
        };
    }
}
