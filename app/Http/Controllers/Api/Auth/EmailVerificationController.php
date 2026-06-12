<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class EmailVerificationController extends Controller
{
    use ApiResponse;

    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect($frontendUrl . '/login?status=error&message=' . urlencode('Đường dẫn xác thực không hợp lệ.'));
        }

        if ($user->hasVerifiedEmail()) {
            return redirect($frontendUrl . '/login?status=info&message=' . urlencode('Tài khoản đã được xác thực trước đó.'));
        }

        if ($user->markEmailAsVerified()) {
            return redirect($frontendUrl . '/login?status=success&message=' . urlencode('Xác thực email thành công. Hãy đăng nhập để bắt đầu!'));
        }

        return redirect($frontendUrl . '/login?status=error&message=' . urlencode('Không thể xác thực email.'));
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success(null, 'Tài khoản đã được xác thực.');
        }

        $key = 'resend_verification_' . $user->id;
        
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return $this->error("Vui lòng thử lại sau {$seconds} giây.", 429);
        }

        RateLimiter::hit($key, 60);

        $user->sendEmailVerificationNotification();

        return $this->success(null, 'Email xác nhận đã được gửi lại.');
    }
}
