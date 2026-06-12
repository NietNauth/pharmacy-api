<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Category::with('parent')
            ->orderByRaw('COALESCE(parent_id, id) ASC')
            ->orderByRaw('parent_id IS NOT NULL ASC')
            ->orderBy('display_order')
            ->orderBy('name');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('slug', 'like', "%$search%");
            });
        }
        
        $user = $request->user('sanctum');
        if (!($user && (is_object($user->role) ? $user->role->value === 'admin' : $user->role === 'admin'))) {
            $query->where('is_active', true);
        }

        if ($request->has('per_page')) {
            $perPage = $request->per_page;
            // Validate perPage
            if (!is_numeric($perPage) || $perPage < 1) $perPage = 15;
            return $this->paginated($query->paginate($perPage));
        }

        return $this->success($query->get());
    }

    public function children(Request $request, $id)
    {
        $query = Category::where('parent_id', $id)->orderBy('display_order')->orderBy('name');
        
        $user = $request->user('sanctum');
        if (!($user && is_object($user->role) ? $user->role->value === 'admin' : ($user && $user->role === 'admin'))) {
            $query->where('is_active', true);
        }

        return $this->success($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'icon_url' => ['nullable', 'url', 'max:512'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'show_on_home' => ['boolean'],
            'display_order' => ['integer', 'min:0'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        } else {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        // Kiểm tra trùng slug thủ công sau khi đã tạo slug
        if (Category::where('slug', $validated['slug'])->exists()) {
            return $this->error('Đường dẫn (slug) này đã tồn tại. Vui lòng chọn tên khác hoặc nhập slug khác.', 422);
        }

        $category = Category::create($validated);
        return $this->success($category, 'Tạo danh mục thành công', 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'icon_url' => ['nullable', 'url', 'max:512'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'show_on_home' => ['boolean'],
            'display_order' => ['integer', 'min:0'],
        ]);

        if (isset($validated['slug']) || isset($validated['name'])) {
            $newSlug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name'] ?? $category->name);
            
            if ($newSlug !== $category->slug) {
                if (Category::where('slug', $newSlug)->where('id', '!=', $id)->exists()) {
                    return $this->error('Đường dẫn (slug) này đã tồn tại ở danh mục khác.', 422);
                }
                $validated['slug'] = $newSlug;
            }
        }

        $category->update($validated);
        return $this->success($category, 'Cập nhật danh mục thành công');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        if ($category->children()->exists() || $category->products()->exists()) {
            return $this->error('Không thể xóa danh mục đang có danh mục con hoặc sản phẩm.', 400);
        }
        
        $category->delete();
        return $this->success(null, 'Đã xóa danh mục thành công');
    }
}
