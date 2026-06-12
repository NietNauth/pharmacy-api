<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create a Featured Parent Category
        $parent = Category::updateOrCreate(
            ['slug' => 'danh-muc-suc-khoe'],
            [
                'name' => 'Danh mục sức khỏe',
                'is_active' => true,
                'is_featured' => true,
                'display_order' => 1,
            ]
        );

        $categories = [
            'Sức khỏe sinh sản',
            'Tai - Mũi - Họng',
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
            'Vitamin - Khoáng chất',
        ];

        foreach ($categories as $index => $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'parent_id' => $parent->id,
                    'name' => $name,
                    'is_active' => true,
                    'is_featured' => false, // Only parents are featured for the section title
                    'display_order' => $index,
                ]
            );
        }

        // 2. Create another Featured Parent Category
        $parent2 = Category::updateOrCreate(
            ['slug' => 'cham-soc-ca-nhan'],
            [
                'name' => 'Chăm sóc cá nhân',
                'is_active' => true,
                'is_featured' => true,
                'display_order' => 2,
            ]
        );

        $personalCare = [
            'Chăm sóc da mặt',
            'Chăm sóc cơ thể',
            'Chăm sóc tóc',
            'Sản phẩm khử mùi',
            'Vệ sinh phụ nữ',
            'Sản phẩm cho nam',
        ];

        foreach ($personalCare as $index => $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'parent_id' => $parent2->id,
                    'name' => $name,
                    'is_active' => true,
                    'is_featured' => false,
                    'display_order' => $index,
                ]
            );
        }
    }
}
