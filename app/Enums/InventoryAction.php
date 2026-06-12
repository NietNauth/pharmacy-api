<?php

namespace App\Enums;

enum InventoryAction: string
{
    case Restock = 'restock';
    case Sale = 'sale';
    case Adjustment = 'adjustment';
    case Reserved = 'reserved';
    case Released = 'released';
    case ExpiredRemoval = 'expired_removal';

    public function label(): string
    {
        return match($this) {
            self::Restock => 'Nhập kho',
            self::Sale => 'Bán hàng',
            self::Adjustment => 'Điều chỉnh',
            self::Reserved => 'Giữ kho (Đặt hàng)',
            self::Released => 'Giải phóng kho (Hủy đơn)',
            self::ExpiredRemoval => 'Hủy do hết hạn',
        };
    }
}
