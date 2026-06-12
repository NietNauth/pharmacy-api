<!DOCTYPE html>
<html>
<head>
    <style>
        .container { max-width: 600px; margin: 0 auto; font-family: sans-serif; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #16a34a; color: #fff; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: #16a34a;">PharmaVN</h1>
        <h2>Đặt lại mật khẩu</h2>
        <p>Xin chào,</p>
        <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản <strong>{{ $email }}</strong>. Link này chỉ sử dụng được 1 lần và sẽ hết hạn trong 60 phút.</p>
        <a href="{{ $resetUrl }}" class="btn">Đặt lại mật khẩu</a>
        <p style="margin-top: 20px; font-size: 12px; color: #666;">Nếu bạn không yêu cầu, hãy bỏ qua email này.</p>
    </div>
</body>
</html>
