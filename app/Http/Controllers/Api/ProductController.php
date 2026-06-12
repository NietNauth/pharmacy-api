<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\CloudinaryService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'images' => function ($q) {
            $q->where('is_primary', true);
        }]);

        $user = $request->user('sanctum');
        if ($user && $user->role->value === 'admin') {
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
        } else {
            $query->where('status', 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('active_ingredient', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $categoryIds = $this->getAllSubCategoryIds($request->category_id);
            $query->whereIn('category_id', $categoryIds);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('requires_prescription')) {
            $query->where('requires_prescription', filter_var($request->requires_prescription, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('min_price')) {
            $query->where(DB::raw('COALESCE(sale_price, base_price)'), '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where(DB::raw('COALESCE(sale_price, base_price)'), '<=', $request->max_price);
        }

        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy(DB::raw('COALESCE(sale_price, base_price)'), 'asc');
                break;
            case 'price_desc':
                $query->orderBy(DB::raw('COALESCE(sale_price, base_price)'), 'desc');
                break;
            case 'rating':
                $query->orderBy('avg_rating', 'desc');
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $products = $query->paginate($perPage);

        return $this->paginated(ProductResource::collection($products));
    }

    public function show($slug)
    {
        $product = Product::with([
            'category', 
            'brand', 
            'images', 
            'attributes', 
            'qas' => function ($q) {
                $q->where('is_visible', true)->latest()->take(5)->with('user');
            }, 
            'inventory',
        ])->where('slug', $slug)->firstOrFail();

        return $this->success(new ProductDetailResource($product));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        
        $slug = Str::slug($data['name']);
        $originalSlug = $slug;
        $count = 1;
        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }
        $data['slug'] = $slug;

        DB::beginTransaction();
        try {
            $product = Product::create($data);

            if (!empty($data['images'])) {
                foreach ($data['images'] as $index => $image) {
                    $product->images()->create([
                        'url' => $image['url'],
                        'is_primary' => $image['is_primary'] ?? false,
                        'sort_order' => $index,
                    ]);
                }
            }

            if (!empty($data['attributes'])) {
                foreach ($data['attributes'] as $attr) {
                    $product->attributes()->create([
                        'attr_key' => $attr['attr_key'],
                        'attr_value' => $attr['attr_value'],
                    ]);
                }
            }

            // Automatically initialize inventory for all branches
            $branches = \App\Models\Branch::all();
            foreach ($branches as $branch) {
                \App\Models\Inventory::create([
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'quantity_available' => 0,
                    'quantity_reserved' => 0,
                    'quantity_minimum' => 5, // Default minimum
                ]);
            }

            DB::commit();

            $product->load(['category', 'brand', 'images', 'attributes', 'inventory']);
            
            return $this->success(new ProductDetailResource($product), 'Tạo sản phẩm thành công', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Lỗi khi tạo sản phẩm: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdateProductRequest $request, $id)
    {
        \Log::info('Updating product ' . $id, $request->all());
        $product = Product::findOrFail($id);
        $data = $request->validated();
        \Log::info('Validated data:', $data);

        DB::beginTransaction();
        try {
            $product->update($data);

            if (isset($data['images'])) {
                $product->images()->delete();
                foreach ($data['images'] as $index => $image) {
                    $product->images()->create([
                        'url' => $image['url'],
                        'is_primary' => $image['is_primary'] ?? false,
                        'sort_order' => $index,
                    ]);
                }
            }

            if (isset($data['attributes'])) {
                $product->attributes()->delete();
                foreach ($data['attributes'] as $attr) {
                    $product->attributes()->create([
                        'attr_key' => $attr['attr_key'],
                        'attr_value' => $attr['attr_value'],
                    ]);
                }
            }

            DB::commit();

            $product->load(['category', 'brand', 'images', 'attributes', 'inventory']);
            
            return $this->success(new ProductDetailResource($product), 'Cập nhật sản phẩm thành công');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Lỗi khi cập nhật sản phẩm: ' . $e->getMessage(), 500);
        }
    }

    public function uploadImage(Request $request, CloudinaryService $cloudinary)
    {
        // For debugging purposes: dd(env('CLOUDINARY_URL')); 

        if (!$request->hasFile('image') && !$request->hasFile('images')) {
            return $this->error('Không tìm thấy file ảnh (image hoặc images[])', 400);
        }

        // Handle multiple images
        if ($request->hasFile('images')) {
            $request->validate([
                'images' => 'required|array',
                'images.*' => 'required|file|mimes:jpg,jpeg,png,webp,avif|max:5120'
            ]);

            try {
                $urls = [];
                foreach ($request->file('images') as $file) {
                    $urls[] = $cloudinary->upload($file, 'products');
                }
                return $this->success(['urls' => $urls], 'Tải loạt ảnh lên thành công');
            } catch (\Exception $e) {
                \Log::error('Cloudinary bulk upload error: ' . $e->getMessage());
                return $this->error('Lỗi khi tải loạt ảnh lên: ' . $e->getMessage(), 500);
            }
        }

        // Handle single image
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp,avif|max:5120'
        ]);

        try {
            $file = $request->file('image');
            $url  = $cloudinary->upload($file, 'products');

            return $this->success(['url' => $url], 'Tải ảnh lên thành công');
        } catch (\Exception $e) {
            \Log::error('Cloudinary upload error: ' . $e->getMessage());
            return $this->error('Lỗi khi tải ảnh lên: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return $this->success(null, 'Đã xóa sản phẩm thành công');
    }

    private function getAllSubCategoryIds($categoryId, $depth = 0)
    {
        // Limit recursion depth to prevent infinite loops (Max 10 levels)
        if ($depth > 10) {
            return [$categoryId];
        }

        $ids = [$categoryId];
        $subCategories = Category::where('parent_id', $categoryId)->pluck('id')->toArray();
        
        foreach ($subCategories as $subId) {
            $ids = array_merge($ids, $this->getAllSubCategoryIds($subId, $depth + 1));
        }
        
        return $ids;
    }
}
