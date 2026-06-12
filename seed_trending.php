<?php

use App\Models\ChatbotSession;
use App\Models\ChatbotMessage;
use Illuminate\Support\Str;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$questions = [
    'Tư vấn thuốc đau đầu hiệu quả nhất',
    'Tư vấn thuốc đau đầu hiệu quả nhất',
    'Tư vấn thuốc đau đầu hiệu quả nhất',
    'Thuốc ho cho trẻ em loại nào tốt?',
    'Thuốc ho cho trẻ em loại nào tốt?',
    'Cách dùng Vitamin tổng hợp như thế nào?',
    'Cách dùng Vitamin tổng hợp như thế nào?',
    'Thuốc giảm cân an toàn',
    'Sản phẩm bổ mắt cho người già',
    'Nước rửa tay diệt khuẩn',
    'Khẩu trang y tế 4 lớp',
    'Thực phẩm chức năng hỗ trợ dạ dày',
    'Thực phẩm chức năng hỗ trợ dạ dày',
    'Dầu gió xanh loại nào tốt?',
    'Miếng dán hạ sốt cho bé',
    'Thuốc trị đau dạ dày'
];

echo "Đang tạo dữ liệu giả lập cho xu hướng...\n";

// Create a dummy session
$session = ChatbotSession::create([
    'session_token' => Str::random(64),
    'channel' => 'web',
    'started_at' => now(),
]);

foreach ($questions as $q) {
    ChatbotMessage::create([
        'session_id' => $session->id,
        'role' => 'user',
        'content' => $q,
        'created_at' => now()->subDays(rand(1, 15)), // Rải rác trong 15 ngày qua
    ]);
}

// Clear cache to see results immediately
\Illuminate\Support\Facades\Cache::forget('chatbot_trending_admin');
\Illuminate\Support\Facades\Cache::forget('chatbot_trending_public');

echo "Xong! Đã thêm " . count($questions) . " câu hỏi và xóa Cache xu hướng.\n";
