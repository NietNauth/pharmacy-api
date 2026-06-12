<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Brand::orderBy('name');
        
        $user = $request->user('sanctum');
        if (!($user && is_object($user->role) ? $user->role->value === 'admin' : ($user && $user->role === 'admin'))) {
            $query->where('is_active', true);
        }

        return $this->success($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'],
            'country_of_origin' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'url', 'max:512'],
            'is_active' => ['boolean'],
        ]);

        $brand = Brand::create($validated);
        return $this->success($brand, 'Tạo thương hiệu thành công', 201);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);
        
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:brands,name,' . $id],
            'country_of_origin' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'url', 'max:512'],
            'is_active' => ['boolean'],
        ]);

        $brand->update($validated);
        return $this->success($brand, 'Cập nhật thương hiệu thành công');
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        if ($brand->products()->exists()) {
            return $this->error('Không thể xóa thương hiệu đang có sản phẩm.', 400);
        }
        
        $brand->delete();
        return $this->success(null, 'Đã xóa thương hiệu thành công');
    }
}
