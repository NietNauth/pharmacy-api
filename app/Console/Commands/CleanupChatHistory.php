<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('chat:cleanup')]
#[Description('Xóa lịch sử trò chuyện cũ hơn 1 tuần và các tệp đính kèm liên quan')]
class CleanupChatHistory extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bắt đầu dọn dẹp lịch sử trò chuyện...');

        $oneWeekAgo = now()->subDays(7);

        // Lấy danh sách tin nhắn có tệp đính kèm cũ hơn 1 tuần
        $messagesWithAttachments = \App\Models\Message::where('created_at', '<', $oneWeekAgo)
            ->whereNotNull('attachment_url')
            ->get();

        $attachmentCount = 0;
        foreach ($messagesWithAttachments as $message) {
            // Lấy path thực tế từ accessor (chúng ta cần path tương đối trong storage)
            // Vì accessor trả về full URL, chúng ta cần lấy path gốc
            $rawPath = $message->getRawOriginal('attachment_url');
            if ($rawPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($rawPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($rawPath);
                $attachmentCount++;
            }
        }

        // Xóa tin nhắn
        $deletedMessagesCount = \App\Models\Message::where('created_at', '<', $oneWeekAgo)->delete();

        // Xóa các cuộc hội thoại không còn tin nhắn nào
        $deletedConversationsCount = \App\Models\Conversation::doesntHave('messages')->delete();

        $this->info("Đã xóa {$deletedMessagesCount} tin nhắn cũ.");
        $this->info("Đã xóa {$attachmentCount} tệp đính kèm.");
        $this->info("Đã dọn dẹp {$deletedConversationsCount} cuộc hội thoại trống.");
        $this->info('Hoàn tất dọn dẹp!');
    }
}
