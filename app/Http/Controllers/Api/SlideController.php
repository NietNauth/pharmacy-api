<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Slide;
use App\Services\CloudinaryService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SlideController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Slide::query();

        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        $slides = $query->orderBy('order', 'asc')->get();

        return $this->success($slides);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image_url' => 'required|string',
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'link' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $slide = Slide::create($request->all());

        return $this->success($slide, 'Tạo slide thành công', 201);
    }

    public function show($id)
    {
        $slide = Slide::findOrFail($id);
        return $this->success($slide);
    }

    public function update(Request $request, $id)
    {
        $slide = Slide::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'image_url' => 'sometimes|required|string',
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'link' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $slide->update($request->all());

        return $this->success($slide, 'Cập nhật slide thành công');
    }

    public function destroy($id)
    {
        $slide = Slide::findOrFail($id);
        $slide->delete();

        return $this->success(null, 'Xóa slide thành công');
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:slides,id'
        ]);

        foreach ($request->ids as $index => $id) {
            Slide::where('id', $id)->update(['order' => $index]);
        }

        return $this->success(null, 'Cập nhật thứ tự thành công');
    }

    public function uploadImage(Request $request, CloudinaryService $cloudinary)
    {
        if (!$request->hasFile('image')) {
            return $this->error('Không tìm thấy file ảnh', 400);
        }

        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp,avif|max:10240'
        ]);

        try {
            $file = $request->file('image');
            $url  = $cloudinary->upload($file, 'slides');

            return $this->success(['url' => $url], 'Tải ảnh lên thành công');
        } catch (\Exception $e) {
            \Log::error('Cloudinary slide upload error: ' . $e->getMessage());
            return $this->error('Lỗi khi tải ảnh lên: ' . $e->getMessage(), 500);
        }
    }
}
