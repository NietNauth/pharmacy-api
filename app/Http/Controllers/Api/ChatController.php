<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $user = Auth::user();

        if ($user->role->value === 'customer') {
            $conversations = Conversation::where('user_id', $user->id)
                ->with(['pharmacist:id,full_name'])
                ->orderBy('last_message_at', 'desc')
                ->get();
        } else {
            $conversations = Conversation::with(['user:id,full_name'])
                ->orderBy('last_message_at', 'desc')
                ->get();
        }

        return $this->success($conversations);
    }

    public function start(Request $request)
    {
        $user = Auth::user();

        // Check if there is already an open conversation for this user
        $conversation = Conversation::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'status' => 'open',
            ]);
        }

        return $this->success($conversation);
    }

    public function show($id)
    {
        $user = Auth::user();
        $conversation = Conversation::findOrFail($id);

        // Authorization check
        if ($user->role->value === 'customer' && $conversation->user_id !== $user->id) {
            return $this->error('Bạn không có quyền xem cuộc hội thoại này.', 403);
        }

        $messages = $conversation->messages()
            ->with('sender:id,full_name,role')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->success([
            'conversation' => $conversation,
            'messages' => $messages
        ]);
    }

    public function send(Request $request, $id)
    {
        $request->validate([
            'body' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        if (!$request->body && !$request->hasFile('attachment')) {
            return $this->error('Nội dung tin nhắn hoặc tệp đính kèm là bắt buộc.', 422);
        }

        $user = Auth::user();
        $conversation = Conversation::findOrFail($id);

        // Authorization check
        if ($user->role->value === 'customer' && $conversation->user_id !== $user->id) {
            return $this->error('Bạn không có quyền gửi tin nhắn trong cuộc hội thoại này.', 403);
        }

        // If a pharmacist sends a message, assign them to the conversation if not already assigned
        if ($user->role->value === 'pharmacist' && !$conversation->pharmacist_id) {
            $conversation->update(['pharmacist_id' => $user->id]);
        }

        $type = 'text';
        $attachmentUrl = null;
        $body = $request->body;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('chat/attachments', 'public');
            $attachmentUrl = $path;
            
            $mime = $file->getMimeType();
            if (str_starts_with($mime, 'image/')) {
                $type = 'image';
            } else {
                $type = 'file';
            }

            if (!$body) {
                $body = $type === 'image' ? '[Hình ảnh]' : '[Tệp tin]';
            }
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'sender_type' => $user->role->value,
            'type' => $type,
            'body' => strip_tags($body), // XSS Protection
            'attachment_url' => $attachmentUrl,
        ]);

        $conversation->update([
            'last_message' => strip_tags($body),
            'last_message_at' => now(),
        ]);

        return $this->success($message->load('sender:id,full_name,role'));
    }
}
