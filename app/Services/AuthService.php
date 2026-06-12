<?php

namespace App\Services;

use App\Exceptions\AccountInactiveException;
use App\Exceptions\EmailNotVerifiedException;
use App\Exceptions\InvalidCredentialsException;
use App\Models\Cart;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        Cart::create([
            'user_id' => $user->id,
        ]);

        $user->sendEmailVerificationNotification();

        $plainToken = $user->createToken('auth_token')->plainTextToken;
        
        $request = request();
        UserSession::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 255),
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'user' => $user,
            'token' => $plainToken,
        ];
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw new InvalidCredentialsException('Tài khoản không tồn tại trong hệ thống.');
        }

        if (!Hash::check($data['password'], $user->password_hash)) {
            throw new InvalidCredentialsException('Mật khẩu không chính xác.');
        }

        Auth::login($user);

        $user = Auth::user();

        if (!$user->is_active) {
            throw new AccountInactiveException('Tài khoản của bạn đã bị khóa hoặc chưa kích hoạt.');
        }

        if (!$user->hasVerifiedEmail()) {
            throw new EmailNotVerifiedException('Tài khoản của bạn chưa được xác minh email.');
        }

        $plainToken = $user->createToken('auth_token')->plainTextToken;
        
        $request = request();
        UserSession::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 255),
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'user' => $user,
            'token' => $plainToken,
        ];
    }
}
