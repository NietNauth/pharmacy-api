<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\ProductImage;
use Illuminate\Support\Str;

class CommonProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create common brands
        $brands = [
            ['name' => 'GSK', 'origin' => 'Anh'],
            ['name' => 'Sanofi', 'origin' => 'Pháp'],
            ['name' => 'DHG Pharma', 'origin' => 'Việt Nam'],
            ['name' => 'Traphaco', 'origin' => 'Việt Nam'],
            ['name' => 'Bayer', 'origin' => 'Đức'],
            ['name' => 'Durex', 'origin' => 'Anh'],
        ];

        $brandModels = [];
        foreach ($brands as $b) {
            $brandModels[$b['name']] = Brand::create([
                'id' => Str::uuid(),
                'name' => $b['name'],
                'country_of_origin' => $b['origin'],
                'is_active' => true,
            ]);
        }

        // 2. Get categories for mapping
        $catTieuHoa = Category::where('name', 'Tiêu hóa')->first();
        $catHoHap = Category::where('name', 'Hô hấp')->first();
        $catVitamin = Category::where('name', 'Vitamin - khoáng chất')->first();
        $catGiamDau = Category::where('name', 'Giảm đau hạ sốt')->orWhere('name', 'Thuốc không kê đơn')->first();
        $catSinhSan = Category::where('name', 'Sức khỏe sinh sản')->first();

        // 3. Common products data
        $products = [
            [
                'name' => 'Panadol Extra',
                'brand' => 'GSK',
                'category' => $catGiamDau,
                'price' => 45000,
                'unit' => 'Hộp',
                'ingredient' => 'Paracetamol, Caffeine',
                'usage' => 'Giảm đau, hạ sốt nhanh chóng.',
            ],
            [
                'name' => 'Berocca Performance',
                'brand' => 'Bayer',
                'category' => $catVitamin,
                'price' => 180000,
                'unit' => 'Tuýp',
                'ingredient' => 'Vitamin nhóm B, C, Magie, Kẽm',
                'usage' => 'Bổ sung vitamin và khoáng chất, giảm mệt mỏi.',
            ],
            [
                'name' => 'Enterogermina 5ml',
                'brand' => 'Sanofi',
                'category' => $catTieuHoa,
                'price' => 150000,
                'unit' => 'Hộp 20 ống',
                'ingredient' => 'Bào tử Bacillus clausii',
                'usage' => 'Điều trị và phòng ngừa rối loạn khuẩn đường ruột.',
            ],
            [
                'name' => 'Xịt mũi Xisat người lớn',
                'brand' => 'DHG Pharma',
                'category' => $catHoHap,
                'price' => 32000,
                'unit' => 'Chai',
                'ingredient' => 'Nước biển sâu vòi phun sương',
                'usage' => 'Vệ sinh mũi, thông mũi, kháng khuẩn.',
            ],
            [
                'name' => 'Bao cao su Durex Invisible',
                'brand' => 'Durex',
                'category' => $catSinhSan,
                'price' => 220000,
                'unit' => 'Hộp 10 cái',
                'ingredient' => 'Mủ cao su thiên nhiên',
                'usage' => 'Tránh thai và ngăn ngừa bệnh lây qua đường tình dục.',
            ],
            [
                'name' => 'Hoạt Huyết Dưỡng Não',
                'brand' => 'Traphaco',
                'category' => $catVitamin,
                'price' => 95000,
                'unit' => 'Hộp',
                'ingredient' => 'Cao Bạch Quả, Cao Đinh Lăng',
                'usage' => 'Bổ não, tăng cường lưu thông máu não.',
            ],
        ];

        // 4. Create a branch if none exists
        $branch = Branch::first() ?? Branch::create([
            'id' => Str::uuid(),
            'name' => 'Pharmacity Quận 1',
            'address' => '123 Lê Lợi, Phường Bến Thành, Quận 1, TP.HCM',
            'phone' => '02812345678',
            'is_active' => true,
        ]);

        // 5. Seed products, inventory, and dummy images
        foreach ($products as $p) {
            $product = Product::create([
                'id' => Str::uuid(),
                'brand_id' => $brandModels[$p['brand']]->id,
                'category_id' => $p['category']?->id ?? Category::first()->id,
                'name' => $p['name'],
                'slug' => Str::slug($p['name']),
                'sku' => strtoupper(Str::random(8)),
                'base_price' => $p['price'],
                'unit' => $p['unit'],
                'active_ingredient' => $p['ingredient'],
                'usage' => $p['usage'],
                'status' => 'active',
                'requires_prescription' => false,
            ]);

            // Add to inventory
            Inventory::create([
                'id' => Str::uuid(),
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'quantity_available' => rand(50, 200),
                'quantity_minimum' => 10,
            ]);

            // Add dummy image
            ProductImage::create([
                'id' => Str::uuid(),
                'product_id' => $product->id,
                'url' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&q=80&w=400',
                'is_primary' => true,
            ]);
        }
    }
}
