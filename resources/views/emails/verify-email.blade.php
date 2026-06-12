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
        <h2>Xác thực email của bạn</h2>
        <p>Xin chào {{ $user->full_name }},</p>
        <p>Vui lòng click vào nút bên dưới để xác thực email của bạn. Link này sẽ hết hạn trong 60 phút.</p>
        <a href="{{ $verificationUrl }}" class="btn">Xác thực ngay</a>
        <p style="margin-top: 20px; font-size: 12px; color: #666;">Nếu bạn không tạo tài khoản, hãy bỏ qua email này.</p>
    </div>
</body>
</html>
