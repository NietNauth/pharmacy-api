<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClearAndSeedPharmacitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clear data except users and migrations
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'brands',
            'categories',
            'products',
            'branches',
            'inventories',
            'orders',
            'order_items',
            'prescriptions',
            'reviews',
            'coupons',
            'cart_items',
            'personal_access_tokens',
            'product_images',
            'product_attributes',
            'inventory_logs',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Populate Pharmacity-style Categories
        $pharmacityData = [
            'Danh mục tủ thuốc' => [
                'featured' => true,
                'children' => [
                    'Sức khỏe sinh sản',
                    'Tai - Mùi - Họng',
                    'Thuốc trị ký sinh trùng',
                    'Cơ - Xương - Khớp',
                    'Truyền nhiễm',
                    'Thận - Tiết niệu',
                    'Da - Tóc - Móng',
                    'Máu',
                    'Mắt',
                    'Hô hấp',
                    'Tâm thần',
                    'Ung thư',
                    'Nội tiết - Chuyển hóa',
                    'Dị ứng',
                    'Tim mạch',
                    'Vitamin - khoáng chất',
                    'Tiểu đường',
                    'Răng - Hàm - Mặt',
                    'Gan',
                    'Tiêu hóa',
                    'Khác',
                ]
            ],
            'Thuốc' => [
                'featured' => true,
                'children' => [
                    'Thuốc không kê đơn',
                    'Thuốc kê đơn',
                    'Thuốc khác',
                    'Vitamin & Thực phẩm chức năng',
                ]
            ],
            'Thực phẩm bảo vệ sức khỏe' => [
                'featured' => true,
                'children' => [
                    'Dành cho trẻ em',
                    'Chăm sóc sắc đẹp',
                    'Nhóm tim mạch',
                    'Nhóm hô hấp',
                    'Nhóm Mắt/Tai/Mũi',
                    'Vitamin và khoáng chất',
                    'Hỗ trợ sinh lý nam nữ',
                    'Chăm sóc gan',
                    'Nhóm thần kinh',
                    'Hỗ trợ giảm cân',
                    'Nhóm đường huyết',
                    'Nhóm cơ xương khớp',
                    'Nhóm dạ dày',
                    'Nhóm thận tiết niệu',
                    'Dành cho phụ nữ mang thai',
                ]
            ],
            'Chăm sóc cá nhân' => [
                'featured' => true,
                'children' => [
                    'Sản phẩm khử mùi',
                    'Sản phẩm phòng tắm',
                    'Chăm sóc tóc',
                    'Chăm sóc răng miệng',
                    'Vệ sinh phụ nữ',
                    'Chăm sóc nam giới',
                    'Chăm sóc cơ thể',
                ]
            ],
            'Mẹ và Bé' => [
                'featured' => true,
                'children' => [
                    'Chăm sóc em bé',
                    'Sản phẩm dành cho mẹ',
                    'Sữa công thức',
                    'Tã bỉm',
                ]
            ],
            'Chăm sóc sắc đẹp' => [
                'featured' => false,
                'children' => [
                    'Chăm sóc mặt',
                    'Sản phẩm chống nắng',
                    'Dụng cụ làm đẹp',
                    'Dược - Mỹ Phẩm',
                ]
            ],
            'Thiết bị y tế' => [
                'featured' => false,
                'children' => [
                    'Dụng cụ kiểm tra',
                    'Máy đo đường huyết',
                    'Nhiệt kế',
                    'Máy xông khí dung',
                    'Thiết bị y tế khác',
                    'Máy đo huyết áp',
                ]
            ],
            'Sản phẩm tiện lợi' => [
                'featured' => false,
                'children' => [
                    'Hàng tổng hợp',
                    'Hàng bách hóa',
                    'Khẩu trang',
                    'Nước rửa tay',
                ]
            ],
        ];

        $order = 1;
        foreach ($pharmacityData as $parentName => $config) {
            $parentSlug = Str::slug($parentName);
            $parent = Category::updateOrCreate(
                ['slug' => $parentSlug],
                [
                    'name' => $parentName,
                    'is_active' => true,
                    'is_featured' => $config['featured'],
                    'display_order' => $order++,
                ]
            );

            foreach ($config['children'] as $childIdx => $childName) {
                $childSlug = Str::slug($childName);
                
                // If child slug conflicts with parent, append parent info
                if (isset($pharmacityData[$childName]) || $childName === $parentName) {
                    $childSlug = $parentSlug . '-' . $childSlug;
                }

                Category::updateOrCreate(
                    ['slug' => $childSlug],
                    [
                        'parent_id' => $parent->id,
                        'name' => $childName,
                        'is_active' => true,
                        'is_featured' => false,
                        'display_order' => $childIdx,
                    ]
                );
            }
        }
    }
}
