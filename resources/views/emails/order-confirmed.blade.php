<!DOCTYPE html>
<html>
<head>
    <style>
        .container { max-width: 600px; margin: 0 auto; font-family: sans-serif; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #16a34a; color: #fff; text-decoration: none; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: #16a34a;">PharmaVN</h1>
        <h2>Đơn hàng #{{ $order->order_code }} đã được xác nhận</h2>
        <p>Xin chào {{ $order->recipient_name }},</p>
        <p>Cảm ơn bạn đã mua sắm tại PharmaVN. Dưới đây là chi tiết đơn hàng của bạn:</p>
        
        <h3>Thông tin giao hàng</h3>
        <p><strong>Người nhận:</strong> {{ $order->recipient_name }} - {{ $order->recipient_phone }}</p>
        @if($order->delivery_type !== 'pickup')
            <p><strong>Địa chỉ:</strong> {{ $order->delivery_address }}</p>
        @else
            <p><strong>Chi nhánh nhận hàng:</strong> {{ $order->branch->name ?? '' }} - {{ $order->branch->address ?? '' }}</p>
        @endif

        <h3>Sản phẩm</h3>
        <table>
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>SL</th>
                    <th>Giá</th>
                    <th>Tổng</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ optional($item->product)->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price) }}đ</td>
                    <td>{{ number_format($item->subtotal) }}đ</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Tạm tính:</strong></td>
                    <td>{{ number_format($order->subtotal) }}đ</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Giảm giá:</strong></td>
                    <td>-{{ number_format($order->discount_amount) }}đ</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Phí vận chuyển:</strong></td>
                    <td>{{ number_format($order->shipping_fee) }}đ</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Tổng cộng:</strong></td>
                    <td><strong style="color: #16a34a;">{{ number_format($order->total) }}đ</strong></td>
                </tr>
            </tfoot>
        </table>
        <p style="margin-top: 20px; font-size: 12px; color: #666;">Trân trọng,<br>Đội ngũ PharmaVN</p>
    </div>
</body>
</html>
