<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChatbotSetting;

class ChatbotSettingSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            [
                'key' => 'chatbot_system_prompt',
                'value' => "Bạn là Dược sĩ AI chuyên nghiệp của nhà thuốc PharmaVN. 
Nhiệm vụ của bạn:
1. Tư vấn sức khỏe tận tâm, chính xác dựa trên kiến thức y khoa.
2. CHỈ gợi ý sản phẩm từ danh sách được cung cấp nếu sản phẩm đó THỰC SỰ liên quan và có ích cho tình trạng của khách hàng.
3. Tuyệt đối KHÔNG nhắc đến hoặc gợi ý các sản phẩm không liên quan (ví dụ: không gợi ý bơm tiêm khi khách bị đau đầu), ngay cả khi chúng có trong danh sách ngữ cảnh.
4. Nếu không có sản phẩm nào phù hợp trong kho, hãy tư vấn hướng điều trị chung và khuyên khách đi khám bác sĩ nếu cần.
5. Luôn giữ thái độ lịch sự, chuyên nghiệp và có trách nhiệm.
6. Trả lời ngắn gọn, súc tích và dễ hiểu cho người bệnh."
            ],
            [
                'key' => 'chatbot_stopwords',
                'value' => [
                    'tôi', 'dùng', 'được', 'cho', 'của', 'là', 'có', 'không', 'với', 'cái',
                    'này', 'nào', 'gì', 'đâu', 'đó', 'đi', 'lại', 'vào', 'ra', 'lên', 'xuống',
                    'cần', 'muốn', 'phải', 'biết', 'thấy', 'làm', 'như', 'về', 'trong', 'ngoài',
                    'thuốc', 'viên', 'hộp', 'chai', 'lọ', 'túi', 'miếng', 'ống', 'bơm', 'tiêm',
                    'bán', 'mua', 'giá', 'bao', 'nhiêu', 'tiền'
                ]
            ],
            [
                'key' => 'manual_suggestions',
                'value' => [
                    'Tư vấn cho tôi các loại Vitamin tổng hợp',
                    'Thuốc giảm đau đầu hiệu quả nhất',
                    'Cách phòng ngừa cảm cúm cho trẻ em',
                    'Thực phẩm chức năng hỗ trợ dạ dày'
                ]
            ]
        ];

        foreach ($settings as $setting) {
            ChatbotSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
