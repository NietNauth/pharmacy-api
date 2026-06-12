<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Resources\UserResource;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = User::with('branch');

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20);

        return $this->paginated(UserResource::collection($users));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'role' => ['required', 'string', 'in:admin,pharmacist,customer'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'password_hash' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'] ?? null,
            'is_verified' => true,
            'is_active' => true,
        ]);

        return $this->success(new UserResource($user->load('branch')), 'Tạo người dùng thành công.', 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);
        
        $user->update($validated);
        
        return $this->success(new UserResource($user->load('branch')), 'Cập nhật người dùng thành công.');
    }

    public function toggleActive($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return $this->error('Bạn không thể khóa chính mình.', 400);
        }
        
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'mở khóa' : 'khóa';
        return $this->success(['is_active' => $user->is_active], "Đã {$status} tài khoản thành công.");
    }
}
