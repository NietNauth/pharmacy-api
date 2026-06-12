<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case COD = 'cod';
    case Momo = 'momo';
    case VNPay = 'vnpay';
    case ZaloPay = 'zalopay';

    public function label(): string
    {
        return match($this) {
            self::COD => 'Thanh toán khi nhận hàng',
            self::Momo => 'Ví MoMo',
            self::VNPay => 'VNPay',
            self::ZaloPay => 'ZaloPay',
        };
    }
}
