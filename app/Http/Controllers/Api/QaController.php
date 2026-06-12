<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Qa\StoreQaRequest;
use App\Http\Resources\QaResource;
use App\Models\ProductQa;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class QaController extends Controller
{
    use ApiResponse;

    public function index($productId, Request $request)
    {
        $query = ProductQa::with(['user'])
            ->where('product_id', $productId)
            ->where('is_visible', true);

        $sort = $request->get('sort', 'newest');
        if ($sort === 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->get('per_page', 10), 50);
        $qas = $query->paginate($perPage);

        return $this->paginated(QaResource::collection($qas));
    }

    public function store($productId, StoreQaRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        try {
            $qa = ProductQa::create([
                'product_id' => $productId,
                'user_id' => $user ? $user->id : null, // Handle null user just in case
                'body' => strip_tags($validated['body']),
                'is_verified_purchase' => false,
            ]);

            $qa->load(['user', 'product']);

            // Notify staff
            $staff = User::getStaff();
            Notification::send($staff, new SystemNotification(
                'Câu hỏi mới về sản phẩm',
                "Khách hàng " . ($user ? $user->full_name : 'Ẩn danh') . " đã đặt câu hỏi cho sản phẩm {$qa->product->name}.",
                "/admin/qas",
                'info'
            ));

            return $this->success(new QaResource($qa), 'Gửi câu hỏi thành công.', 201);
        } catch (\Exception $e) {
            return $this->error('Lỗi khi lưu câu hỏi: ' . $e->getMessage(), 500);
        }
    }

    public function adminIndex(Request $request)
    {
        $query = ProductQa::with(['user', 'product.images']);

        if ($request->has('is_visible')) {
            $query->where('is_visible', filter_var($request->is_visible, FILTER_VALIDATE_BOOLEAN));
        }

        $qas = $query->latest()->paginate(20);

        return $this->paginated(QaResource::collection($qas));
    }

    public function adminReply($qaId, Request $request)
    {
        $request->validate([
            'reply' => ['required', 'string', 'max:1000'],
        ]);

        $qa = ProductQa::findOrFail($qaId);
        
        $qa->update([
            'admin_reply' => strip_tags($request->reply),
            'admin_replied_at' => now(),
        ]);

        // Notify user if they are logged in
        if ($qa->user_id) {
            $qa->user->notify(new SystemNotification(
                'Phản hồi câu hỏi',
                "Dược sĩ đã trả lời câu hỏi của bạn về sản phẩm {$qa->product->name}.",
                "/products/{$qa->product->slug}",
                'success'
            ));
        }

        return $this->success(new QaResource($qa), 'Đã phản hồi câu hỏi.');
    }

    public function adminToggleVisibility($qaId)
    {
        $qa = ProductQa::findOrFail($qaId);
        
        $qa->update([
            'is_visible' => !$qa->is_visible
        ]);

        return $this->success(new QaResource($qa), 'Đã cập nhật trạng thái hiển thị.');
    }

    public function adminDestroy($qaId)
    {
        $qa = ProductQa::findOrFail($qaId);
        $qa->delete();

        return $this->success(null, 'Đã xóa câu hỏi thành công.');
    }
}
