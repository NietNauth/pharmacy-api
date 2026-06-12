<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\AccountInactiveException;
use App\Exceptions\EmailNotVerifiedException;
use App\Exceptions\InvalidCredentialsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserAddressResource;
use App\Http\Resources\UserResource;
use App\Models\UserAddress;
use App\Models\UserSession;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated());

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Đăng ký thành công', 201);
    }

    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request->validated());

            return $this->success([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Đăng nhập thành công');

        } catch (AccountInactiveException|EmailNotVerifiedException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (InvalidCredentialsException $e) {
            return $this->error($e->getMessage(), 401);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        if ($token) {
            $tokenHash = hash('sha256', $request->bearerToken());
            UserSession::where('token_hash', $tokenHash)->delete();
            $token->delete();
        }

        return $this->success(null, 'Đăng xuất thành công');
    }

    public function logoutAll(Request $request)
    {
        $user = $request->user();

        $user->tokens()->delete();
        UserSession::where('user_id', $user->id)->delete();

        return $this->success(null, 'Đã đăng xuất khỏi tất cả thiết bị');
    }

    public function me(Request $request)
    {
        return $this->success(new UserResource($request->user()));
    }

    public function addresses(Request $request)
    {
        $addresses = $request->user()->addresses;
        return $this->success(UserAddressResource::collection($addresses));
    }

    public function storeAddress(Request $request)
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:255',
            'address_line' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'is_default' => 'boolean',
        ]);

        $user = $request->user();

        if (array_key_exists('is_default', $validated) && $validated['is_default']) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($validated);

        if ($user->addresses()->count() === 1) {
            $address->update(['is_default' => true]);
        }

        return $this->success(new UserAddressResource($address), 'Thêm địa chỉ thành công', 201);
    }

    public function destroyAddress(Request $request, $id)
    {
        $address = UserAddress::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$address) {
            return $this->error('Không tìm thấy địa chỉ', 404);
        }

        $address->delete();

        return $this->success(null, 'Đã xóa địa chỉ');
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|regex:/(84|0[3|5|7|8|9])+([0-9]{8})\b/',
        ], [
            'full_name.required' => 'Vui lòng nhập họ tên.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
        ]);

        $user = $request->user();
        $user->update($validated);

        return $this->success(new UserResource($user), 'Cập nhật hồ sơ thành công');
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'confirmed'],
        ], [
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'new_password.regex' => 'Mật khẩu phải chứa ít nhất 1 chữ hoa và 1 số.',
            'new_password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $user = $request->user();

        if (!\Illuminate\Support\Facades\Hash::check($validated['current_password'], $user->password_hash)) {
            return $this->error('Mật khẩu hiện tại không chính xác', 422);
        }

        if (\Illuminate\Support\Facades\Hash::check($validated['new_password'], $user->password_hash)) {
            return $this->error('Mật khẩu mới không được trùng với mật khẩu hiện tại', 422);
        }

        $user->update([
            'password_hash' => \Illuminate\Support\Facades\Hash::make($validated['new_password'])
        ]);

        return $this->success(null, 'Đổi mật khẩu thành công');
    }
}
