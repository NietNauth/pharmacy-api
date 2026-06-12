<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(20);
        return $this->paginated($notifications);
    }

    public function read(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return $this->success(null, 'Đã đánh dấu đọc thông báo.');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return $this->success(null, 'Đã đánh dấu đọc tất cả thông báo.');
    }
}
