<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Branch::query();

        $user = $request->user('sanctum');
        if (!($user && (is_object($user->role) ? $user->role->value === 'admin' : $user->role === 'admin'))) {
            $query->where('is_active', true);
        }

        return $this->success($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:512'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'is_active' => ['boolean'],
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $branch = Branch::create($validated);

            // Automatically initialize inventory for all products in this new branch
            $products = \App\Models\Product::all();
            foreach ($products as $product) {
                \App\Models\Inventory::create([
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'quantity_available' => 0,
                    'quantity_reserved' => 0,
                    'quantity_minimum' => 5,
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();
            return $this->success($branch, 'Tạo chi nhánh thành công', 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return $this->error('Lỗi khi tạo chi nhánh: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:512'],
            'city' => ['sometimes', 'string', 'max:100'],
            'district' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'is_active' => ['boolean'],
        ]);

        $branch->update($validated);
        return $this->success($branch, 'Cập nhật chi nhánh thành công');
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        if ($branch->inventory()->exists()) {
            return $this->error('Không thể xóa chi nhánh này vì vẫn còn dữ liệu tồn kho liên quan. Hãy xóa dữ liệu tồn kho trước hoặc chuyển chi nhánh sang trạng thái tạm ngưng.', 422);
        }

        $branch->delete();
        return $this->success(null, 'Đã xóa chi nhánh thành công');
    }
}
