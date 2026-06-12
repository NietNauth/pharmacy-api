        <?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\ProductImage;
use App\Models\ProductAttribute;
use Illuminate\Support\Str;

class PharmacityProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clean existing products, inventory, attributes, images
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Product::truncate();
        ProductImage::truncate();
        ProductAttribute::truncate();
        Inventory::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Setup standard brands with origins
        $brandsData = [
            'GSK' => 'Anh',
            'Sanofi' => 'Pháp',
            'DHG Pharma' => 'Việt Nam',
            'Traphaco' => 'Việt Nam',
            'Bayer' => 'Đức',
            'Durex' => 'Anh',
            'Pharmacity' => 'Việt Nam',
            'AstraZeneca' => 'Thụy Điển',
            'Pfizer' => 'Mỹ',
            'Rohto' => 'Nhật Bản',
            'Mega We Care' => 'Thái Lan',
            'Johnson & Johnson' => 'Mỹ',
            'Unilever' => 'Anh',
            'Colgate-Palmolive' => 'Mỹ',
            'P&G' => 'Mỹ',
            'La Roche-Posay' => 'Pháp',
            'Anessa' => 'Nhật Bản',
            'Omron' => 'Nhật Bản',
            'Pigeon' => 'Nhật Bản',
            'Huggies' => 'Mỹ',
            'Bobby' => 'Nhật Bản',
            'Meiji' => 'Nhật Bản',
            'Bioderma' => 'Pháp',
            'DHC' => 'Nhật Bản',
            'Blackmores' => 'Úc',
            'Elevit' => 'Đức',
            'OPC' => 'Việt Nam',
            'Nam Dược' => 'Việt Nam',
            'L\'Oreal' => 'Pháp',
            'Cetaphil' => 'Mỹ',
            'Gillette' => 'Mỹ',
            'Abbott' => 'Mỹ',
            'Stada' => 'Đức',
            'Boston' => 'Việt Nam'
        ];

        $brandModels = [];
        foreach ($brandsData as $name => $origin) {
            $brandModels[$name] = Brand::updateOrCreate(
                ['name' => $name],
                [
                    'id' => Str::uuid(),
                    'country_of_origin' => $origin,
                    'is_active' => true,
                ]
            );
        }

        // 3. Get or Create Branch
        $branch = Branch::first() ?? Branch::create([
            'id' => Str::uuid(),
            'name' => 'Pharmacity Quận 1',
            'address' => '123 Lê Lợi, Phường Bến Thành, Quận 1, TP.HCM',
            'phone' => '02812345678',
            'is_active' => true,
        ]);

        // 4. Fetch all categories
        $categories = Category::all();

        // 5. Hardcoded high-quality products for main categories
        $handcraftedProducts = $this->getHandcraftedProducts();

        // 6. Loop through each category and seed 5 products
        foreach ($categories as $category) {
            $slug = $category->slug;
            $productsToSeed = [];

            // Check if we have handcrafted products for this category slug
            if (isset($handcraftedProducts[$slug])) {
                $productsToSeed = $handcraftedProducts[$slug];
            } else {
                // If not, generate realistic products dynamically based on category name & type
                $productsToSeed = $this->generateDynamicProducts($category->name, $category->id);
            }

            // Seed exactly 5 products
            foreach ($productsToSeed as $index => $pData) {
                // Determine Brand ID
                $brandName = $pData['brand'] ?? 'Pharmacity';
                $brandId = isset($brandModels[$brandName]) ? $brandModels[$brandName]->id : null;

                // Set prices
                $basePrice = $pData['price'] ?? rand(20, 250) * 1000;
                $salePrice = (rand(1, 100) > 60) ? round($basePrice * 0.9) : null; // 40% chance of 10% discount

                // Check and limit description length / attributes length
                $name = $pData['name'];
                $slugName = Str::slug($name) . '-' . Str::random(5);
                $sku = $pData['sku'] ?? (strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 4)) . rand(1000, 9999));

                // Create Product
                $product = Product::create([
                    'id' => Str::uuid(),
                    'category_id' => $category->id,
                    'brand_id' => $brandId,
                    'name' => $name,
                    'slug' => $slugName,
                    'sku' => $sku,
                    'usage' => $pData['usage'] ?? 'Xem chi tiết trên bao bì sản phẩm hoặc hướng dẫn sử dụng.',
                    'notes' => $pData['notes'] ?? 'Bảo quản nơi khô ráo, thoáng mát, tránh ánh nắng trực tiếp.',
                    'requires_prescription' => $pData['requires_prescription'] ?? false,
                    'status' => 'active',
                    'base_price' => $basePrice,
                    'sale_price' => $salePrice,
                    'unit' => $pData['unit'] ?? 'Hộp',
                    'dosage_form' => $pData['dosage_form'] ?? null,
                    'active_ingredient' => $pData['active_ingredient'] ?? null,
                    'manufacturer' => $pData['manufacturer'] ?? ($brandName !== 'Pharmacity' ? $brandName : 'Pharmacity Việt Nam'),
                    'avg_rating' => number_format(4.0 + (rand(0, 10) / 10), 2),
                    'review_count' => rand(5, 120),
                ]);

                // Create Product Image
                // We use high-quality Unsplash image URLs corresponding to themes if possible
                $imageUrl = $this->getPlaceholderImageUrl($category->name, $index);
                ProductImage::create([
                    'id' => Str::uuid(),
                    'product_id' => $product->id,
                    'url' => $imageUrl,
                    'is_primary' => true,
                    'sort_order' => 0
                ]);

                // Add to Inventory
                Inventory::create([
                    'id' => Str::uuid(),
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'quantity_available' => rand(30, 150),
                    'quantity_reserved' => 0,
                    'quantity_minimum' => 5,
                    'expiry_date' => now()->addMonths(rand(12, 36))->toDateString(),
                ]);

                // Create Key-Value Attributes
                $attributes = [
                    'Quy cách' => $pData['unit'] ?? 'Hộp',
                    'Thương hiệu' => $brandName,
                    'Xuất xứ' => isset($brandsData[$brandName]) ? $brandsData[$brandName] : 'Việt Nam',
                    'Nhà sản xuất' => $product->manufacturer,
                ];

                if ($product->active_ingredient) {
                    $attributes['Hoạt chất'] = $product->active_ingredient;
                }
                if ($product->dosage_form) {
                    $attributes['Dạng bào chế'] = $product->dosage_form;
                }

                foreach ($attributes as $key => $val) {
                    ProductAttribute::create([
                        'id' => Str::uuid(),
                        'product_id' => $product->id,
                        'attr_key' => $key,
                        'attr_value' => $val,
                    ]);
                }
            }
        }
    }

    /**
     * Get pre-defined handcrafted high-quality products for main categories
     */
    private function getHandcraftedProducts(): array
    {
        return [
            // Sức khỏe sinh sản
            'suc-khoe-sinh-san' => [
                [
                    'name' => 'Bao cao su Durex Invisible Hộp 10 cái',
                    'brand' => 'Durex',
                    'price' => 220000,
                    'unit' => 'Hộp 10 cái',
                    'active_ingredient' => 'Mủ cao su thiên nhiên',
                    'dosage_form' => 'Bao cao su',
                    'usage' => 'Phòng tránh thai và các bệnh lây truyền qua đường tình dục.',
                    'notes' => 'Kiểm tra hạn sử dụng trước khi dùng. Không dùng lại bao cao su đã qua sử dụng.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Thuốc tránh thai khẩn cấp Postinor 1',
                    'brand' => 'Sanofi',
                    'price' => 36000,
                    'unit' => 'Hộp 1 viên',
                    'active_ingredient' => 'Levonorgestrel 1.5mg',
                    'dosage_form' => 'Viên nén',
                    'usage' => 'Tránh thai khẩn cấp trong vòng 72 giờ sau giao hợp không an toàn.',
                    'notes' => 'Không dùng quá 2 lần trong một chu kỳ kinh nguyệt.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Gel bôi trơn Durex Play Classic 50ml',
                    'brand' => 'Durex',
                    'price' => 90000,
                    'unit' => 'Chai',
                    'active_ingredient' => 'Gốc nước sinh học',
                    'dosage_form' => 'Gel bôi trơn',
                    'usage' => 'Tăng độ nhờn, bôi trơn âm đạo và giảm khô hạn khi quan hệ.',
                    'notes' => 'Tránh tiếp xúc với mắt, vùng da bị trầy xước. Sử dụng trong vòng 3 tháng sau khi mở nắp.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Que thử thai nhanh Quickstrip',
                    'brand' => 'Pharmacity',
                    'price' => 15000,
                    'unit' => 'Hộp 1 que',
                    'active_ingredient' => 'Kháng thể hCG',
                    'dosage_form' => 'Que thử',
                    'usage' => 'Phát hiện sớm thai kỳ bằng cách đo nồng độ hCG trong nước tiểu.',
                    'notes' => 'Sử dụng ngay khi lấy que ra khỏi túi đựng. Nên thử vào buổi sáng sớm.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Dung dịch vệ sinh Lactacyd Ngày Dịu Nhẹ 250ml',
                    'brand' => 'Sanofi',
                    'price' => 85000,
                    'unit' => 'Chai',
                    'active_ingredient' => 'Acid lactic, Lactoserum',
                    'dosage_form' => 'Dung dịch',
                    'usage' => 'Vệ sinh vùng kín hàng ngày, ngăn ngừa ngứa, mùi hôi và viêm nhiễm phụ khoa.',
                    'notes' => 'Sản phẩm chỉ dùng ngoài da, không thụt rửa sâu bên trong.',
                    'requires_prescription' => false,
                ]
            ],

            // Tai - Mũi - Họng
            'tai-mui-hong' => [
                [
                    'name' => 'Xịt mũi nước biển sâu Xisat người lớn 75ml',
                    'brand' => 'DHG Pharma',
                    'price' => 32000,
                    'unit' => 'Chai',
                    'active_ingredient' => 'Nước biển sâu nguyên chất và tinh dầu khuynh diệp',
                    'dosage_form' => 'Dung dịch xịt mũi',
                    'usage' => 'Làm sạch mũi, kháng khuẩn, kháng viêm, phòng ngừa viêm mũi dị ứng và nghẹt mũi.',
                    'notes' => 'Không sử dụng cho người mẫn cảm với bất kỳ thành phần nào của sản phẩm.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Viên ngậm kháng khuẩn giảm đau họng Strepsils Cool',
                    'brand' => 'GSK',
                    'price' => 38000,
                    'unit' => 'Hộp 2 vỉ x 12 viên',
                    'active_ingredient' => 'Dichlorobenzyl Alcohol 1.2mg, Amylmetacresol 0.6mg',
                    'dosage_form' => 'Viên ngậm',
                    'usage' => 'Giảm nhanh các triệu chứng đau họng, sát trùng họng và vòm miệng.',
                    'notes' => 'Trẻ em dưới 6 tuổi không nên dùng để tránh nguy cơ nghẹn viên.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Siro ho bổ phế Bảo Thanh giảm ho nhanh 125ml',
                    'brand' => 'Nam Dược',
                    'price' => 40000,
                    'unit' => 'Chai',
                    'active_ingredient' => 'Bối mẫu, Tỳ bà diệp, Sa sâm, Cát cánh, Trần bì',
                    'dosage_form' => 'Siro ho',
                    'usage' => 'Trị ho khan, ho có đờm, viêm họng, viêm phế quản và đau rát cổ họng.',
                    'notes' => 'Thận trọng cho người bệnh tiểu đường vì siro có chứa đường cát.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Dung dịch muối sinh lý Physiodose kháng khuẩn 40 ống x 5ml',
                    'brand' => 'Sanofi',
                    'price' => 150000,
                    'unit' => 'Hộp 40 ống',
                    'active_ingredient' => 'Natri Clorid 0.9%',
                    'dosage_form' => 'Dung dịch nhỏ mắt mũi',
                    'usage' => 'Vệ sinh mắt, mũi, tai hàng ngày cho trẻ sơ sinh và người lớn.',
                    'notes' => 'Không sử dụng chung một ống cho cả mắt và mũi. Tránh nhiễm khuẩn chéo.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Xịt keo ong Ogic giảm ho dịu họng thiên nhiên 30ml',
                    'brand' => 'Pharmacity',
                    'price' => 95000,
                    'unit' => 'Chai',
                    'active_ingredient' => 'Keo ong Propolis, mật ong Manuka',
                    'dosage_form' => 'Dung dịch xịt họng',
                    'usage' => 'Dịu họng rát, tăng cường đề kháng vòm họng và giảm viêm loét miệng.',
                    'notes' => 'Lắc đều trước khi xịt. Tránh xịt vào mắt.',
                    'requires_prescription' => false,
                ]
            ],

            // Cơ - Xương - Khớp
            'co-xuong-khop' => [
                [
                    'name' => 'Miếng dán giảm đau nhức Salonpas Hộp 20 miếng',
                    'brand' => 'Rohto',
                    'price' => 28000,
                    'unit' => 'Hộp 20 miếng',
                    'active_ingredient' => 'Methyl Salicylate 6.29g, Menthol 5.71g',
                    'dosage_form' => 'Miếng dán da',
                    'usage' => 'Giảm đau mỏi cơ, khớp, đau vai, đau lưng và bầm tím chấn thương nhẹ.',
                    'notes' => 'Không dùng trên vết thương hở hoặc vùng da bị tổn thương nặng.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Viên uống bổ khớp Jex Max giúp khớp dẻo dai',
                    'brand' => 'Mega We Care',
                    'price' => 350000,
                    'unit' => 'Chai 30 viên',
                    'active_ingredient' => 'Peptan, Undenatured Collagen Type II',
                    'dosage_form' => 'Viên nang',
                    'usage' => 'Nuôi dưỡng sụn khớp, hỗ trợ giảm đau xương khớp cấp và mãn tính.',
                    'notes' => 'Thích hợp cho người trên 18 tuổi bị các vấn đề về thoái hóa khớp.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Gel giảm đau kháng viêm nhanh Voltaren Emulgel 20g',
                    'brand' => 'GSK',
                    'price' => 75000,
                    'unit' => 'Tuýp',
                    'active_ingredient' => 'Diclofenac Diethylamine 1.16%',
                    'dosage_form' => 'Gel bôi da',
                    'usage' => 'Điều trị tại chỗ đau viêm cơ, gân, dây chằng do chấn thương thể thao.',
                    'notes' => 'Rửa sạch tay sau khi thoa gel. Không thoa lên vùng niêm mạc.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Viên uống Glucosamine 1500mg Orihiro hỗ trợ sụn',
                    'brand' => 'Rohto',
                    'price' => 580000,
                    'unit' => 'Chai 900 viên',
                    'active_ingredient' => 'Glucosamine Sulfat chiết xuất từ cua biển',
                    'dosage_form' => 'Viên nén',
                    'usage' => 'Bổ sung dịch khớp, tăng độ đàn hồi sụn và hỗ trợ phòng loãng xương.',
                    'notes' => 'Thận trọng với người dị ứng hải sản tôm, cua.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Thuốc kháng viêm giảm đau xương khớp Celebrex 200mg',
                    'brand' => 'Pfizer',
                    'price' => 350000,
                    'unit' => 'Hộp 30 viên',
                    'active_ingredient' => 'Celecoxib 200mg',
                    'dosage_form' => 'Viên nang cứng',
                    'usage' => 'Giảm đau và kháng viêm khớp dạng thấp, thoái hóa khớp cấp tính.',
                    'notes' => 'Chỉ sử dụng theo đúng chỉ dẫn của bác sĩ. Có nguy cơ ảnh hưởng tim mạch.',
                    'requires_prescription' => true,
                ]
            ],

            // Thuốc không kê đơn
            'thuoc-khong-ke-don' => [
                [
                    'name' => 'Thuốc giảm đau hạ sốt nhanh Panadol Extra',
                    'brand' => 'GSK',
                    'price' => 45000,
                    'unit' => 'Hộp 120 viên',
                    'active_ingredient' => 'Paracetamol 500mg, Caffeine 65mg',
                    'dosage_form' => 'Viên nén',
                    'usage' => 'Giảm đau đầu, đau nửa đầu, đau họng, đau răng, hạ sốt nhanh chóng.',
                    'notes' => 'Không dùng chung với các thuốc khác chứa paracetamol. Tránh dùng quá liều gây hại gan.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Thuốc cảm cúm hắt hơi sổ mũi Decolgen Forte',
                    'brand' => 'Sanofi',
                    'price' => 32000,
                    'unit' => 'Hộp 100 viên',
                    'active_ingredient' => 'Paracetamol 500mg, Phenylephrine HCl 10mg, Chlorpheniramine 2mg',
                    'dosage_form' => 'Viên nén',
                    'usage' => 'Điều trị hiệu quả các triệu chứng cảm cúm, sốt, nghẹt mũi, sổ mũi.',
                    'notes' => 'Có thể gây buồn ngủ. Tránh vận hành máy móc hay lái xe khi dùng thuốc.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Thuốc trị tiêu chảy Berberin OPC 50mg',
                    'brand' => 'OPC',
                    'price' => 12000,
                    'unit' => 'Lọ 100 viên',
                    'active_ingredient' => 'Berberin Clorid 50mg',
                    'dosage_form' => 'Viên nén',
                    'usage' => 'Điều trị lỵ trực trùng, hội chứng lỵ, viêm ruột và tiêu chảy nhiễm khuẩn.',
                    'notes' => 'Không sử dụng cho phụ nữ có thai vì có nguy cơ kích thích co bóp tử cung.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Thuốc hạ sốt nhanh trẻ em Hapacol 250 vị cam ngọt',
                    'brand' => 'DHG Pharma',
                    'price' => 48000,
                    'unit' => 'Hộp 24 gói',
                    'active_ingredient' => 'Paracetamol 250mg',
                    'dosage_form' => 'Thuốc bột sủi bọt',
                    'usage' => 'Hạ sốt, giảm đau răng, đau họng cho trẻ em từ 4 đến 9 tuổi.',
                    'notes' => 'Hòa tan hoàn toàn thuốc bột trong nước ấm trước khi cho trẻ uống.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Nước nhỏ mắt sinh lý NaCl 0.9% Pharmacity sạch bụi',
                    'brand' => 'Pharmacity',
                    'price' => 5000,
                    'unit' => 'Lọ 10ml',
                    'active_ingredient' => 'Natri Clorid 0.9%',
                    'dosage_form' => 'Dung dịch nhỏ mắt mũi',
                    'usage' => 'Rửa mắt bụi bẩn, nghẹt mũi nhẹ và làm sạch vỉ vết thương hở nông.',
                    'notes' => 'Đậy kín nắp sau khi dùng. Hủy sau 15 ngày mở nắp.',
                    'requires_prescription' => false,
                ]
            ],

            // Thuốc kê đơn
            'thuoc-ke-don' => [
                [
                    'name' => 'Thuốc kháng sinh diệt khuẩn Augmentin 1g',
                    'brand' => 'GSK',
                    'price' => 350000,
                    'unit' => 'Hộp 14 viên',
                    'active_ingredient' => 'Amoxicillin 875mg, Acid Clavulanic 125mg',
                    'dosage_form' => 'Viên nén bao phim',
                    'usage' => 'Điều trị nhiễm khuẩn đường hô hấp trên & dưới, nhiễm khuẩn tiết niệu và da.',
                    'notes' => 'Thuốc kê đơn bắt buộc. Phải dùng đúng liều lượng và đủ ngày kháng sinh để tránh kháng thuốc.',
                    'requires_prescription' => true,
                ],
                [
                    'name' => 'Thuốc kháng axit dạ dày Nexium Mups 20mg',
                    'brand' => 'AstraZeneca',
                    'price' => 380000,
                    'unit' => 'Hộp 14 viên',
                    'active_ingredient' => 'Esomeprazole 20mg',
                    'dosage_form' => 'Viên nén bao phim',
                    'usage' => 'Điều trị viêm loét dạ dày tá tràng, trào ngược dạ dày thực quản (GERD).',
                    'notes' => 'Uống trước bữa ăn sáng hoặc tối ít nhất 1 giờ. Không nhai nát viên thuốc.',
                    'requires_prescription' => true,
                ],
                [
                    'name' => 'Thuốc kiểm soát huyết áp Coversyl 5mg',
                    'brand' => 'Sanofi',
                    'price' => 290000,
                    'unit' => 'Hộp 30 viên',
                    'active_ingredient' => 'Perindopril Arginine 5mg',
                    'dosage_form' => 'Viên nén bao phim',
                    'usage' => 'Điều trị tăng huyết áp vô căn, suy tim sung huyết và giảm nguy cơ tai biến mạch máu não.',
                    'notes' => 'Nên uống vào buổi sáng trước khi ăn để tăng hiệu quả tối đa.',
                    'requires_prescription' => true,
                ],
                [
                    'name' => 'Thuốc điều trị gout cấp Colchicine 1mg',
                    'brand' => 'DHG Pharma',
                    'price' => 150000,
                    'unit' => 'Hộp 20 viên',
                    'active_ingredient' => 'Colchicine 1mg',
                    'dosage_form' => 'Viên nén',
                    'usage' => 'Cắt cơn gout cấp tính, phòng ngừa tái phát cơn gout ở bệnh nhân mãn tính.',
                    'notes' => 'Uống ngay khi xuất hiện dấu hiệu đầu tiên của cơn đau. Tuân thủ liều lượng để tránh tiêu chảy.',
                    'requires_prescription' => true,
                ],
                [
                    'name' => 'Thuốc kháng sinh thế hệ mới Zinnat 500mg',
                    'brand' => 'GSK',
                    'price' => 260000,
                    'unit' => 'Hộp 10 viên',
                    'active_ingredient' => 'Cefuroxime Axetil 500mg',
                    'dosage_form' => 'Viên nén bao phim',
                    'usage' => 'Điều trị nhiễm trùng tai mũi họng, viêm phổi cấp, nhiễm trùng da cơ xương khớp.',
                    'notes' => 'Uống sau khi ăn no để đạt khả năng hấp thu tốt nhất vào máu.',
                    'requires_prescription' => true,
                ]
            ],

            // Khẩu trang
            'khau-trang' => [
                [
                    'name' => 'Khẩu trang y tế 4 lớp kháng khuẩn Pharmacity',
                    'brand' => 'Pharmacity',
                    'price' => 45000,
                    'unit' => 'Hộp 50 cái',
                    'usage' => 'Lọc bụi bẩn, vi khuẩn, phấn hoa và các giọt bắn lây nhiễm bệnh qua đường hô hấp.',
                    'notes' => 'Sản phẩm sử dụng 1 lần. Không giặt tái sử dụng.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Khẩu trang 3D thông minh Pharmacity kháng bụi mịn PM2.5',
                    'brand' => 'Pharmacity',
                    'price' => 25000,
                    'unit' => 'Hộp 10 cái',
                    'usage' => 'Kháng bụi mịn PM2.5 vượt trội, ôm khít gương mặt không lem son môi.',
                    'notes' => 'Hạn chế kéo dãn quai đeo nhiều lần để giữ độ ôm khít lý tưởng.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Khẩu trang vải kháng khuẩn dùng nhiều lần cao cấp',
                    'brand' => 'Pharmacity',
                    'price' => 15000,
                    'unit' => 'Cái',
                    'usage' => 'Bảo vệ mặt khỏi tia UV mặt trời và cản lọc vi khuẩn đường bụi bẩn.',
                    'notes' => 'Giặt nhẹ nhàng bằng xà phòng trung tính sau mỗi lần sử dụng.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Khẩu trang trẻ em 3D Mask Pharmacity cho bé từ 3-8 tuổi',
                    'brand' => 'Pharmacity',
                    'price' => 22000,
                    'unit' => 'Hộp 10 cái',
                    'usage' => 'Thiết kế dễ thương ôm khít mặt bé, chất liệu siêu mềm mịn không đau tai.',
                    'notes' => 'Theo dõi bé khi đeo, tránh trẻ nhai cắn khẩu trang.',
                    'requires_prescription' => false,
                ],
                [
                    'name' => 'Khẩu trang bảo hộ 3M N95 có van thở chống bụi độc',
                    'brand' => 'Pharmacity',
                    'price' => 35000,
                    'unit' => 'Cái',
                    'usage' => 'Lọc hơn 95% hạt bụi cực nhỏ trong môi trường hóa chất, ô nhiễm nặng.',
                    'notes' => 'Nên thay mới khi có dấu hiệu khó thở hoặc khẩu trang bị ướt.',
                    'requires_prescription' => false,
                ]
            ]
        ];
    }

    /**
     * Generate 5 highly realistic products dynamically based on category name
     */
    private function generateDynamicProducts(string $categoryName, string $categoryId): array
    {
        $products = [];
        $brandsList = ['GSK', 'Sanofi', 'DHG Pharma', 'Traphaco', 'Bayer', 'Pharmacity', 'Mega We Care', 'Johnson & Johnson', 'Unilever', 'Colgate-Palmolive', 'La Roche-Posay', 'Anessa', 'Omron', 'Pigeon', 'Huggies', 'Bobby', 'Bioderma', 'DHC', 'Blackmores', 'Abbott'];
        
        for ($i = 1; $i <= 5; $i++) {
            $brand = $brandsList[array_rand($brandsList)];
            
            // Tailor generated product details based on category name keyword
            $name = "";
            $price = rand(30, 280) * 1000;
            $unit = "Hộp";
            $dosageForm = "Viên nén";
            $activeIngredient = "Chiết xuất dược liệu tự nhiên";
            $usage = "Hỗ trợ cải thiện tình trạng sức khỏe và phục hồi năng lượng cơ thể.";
            $notes = "Đọc kỹ hướng dẫn sử dụng trước khi dùng. Thực phẩm này không phải là thuốc, không có tác dụng thay thế thuốc chữa bệnh.";
            $requiresPrescription = false;

            if (str_contains($categoryName, 'trẻ em') || str_contains($categoryName, 'em bé') || str_contains($categoryName, 'Bé')) {
                $brand = in_array($brand, ['Pigeon', 'Huggies', 'Bobby', 'Pharmacity', 'DHG Pharma']) ? $brand : 'Pigeon';
                $names = [
                    "Siro bổ sung Canxi và Vitamin D3 cho bé Baby C",
                    "Dầu tắm gội toàn thân em bé dịu nhẹ Pigeon 200ml",
                    "Phấn rôm em bé Johnson's Baby ngừa hăm tã",
                    "Kẹo dẻo bổ sung Vitamin tổng hợp cho bé yêu",
                    "Khăn ướt không mùi em bé Pharmacity cao cấp"
                ];
                $name = $names[$i-1];
                $unit = ($i === 2 || $i === 3) ? "Chai" : ($i === 5 ? "Gói" : "Hộp");
                $dosageForm = ($i === 1) ? "Siro" : (($i === 4) ? "Kẹo dẻo" : "Dung dịch");
                $activeIngredient = ($i === 1) ? "Canxi nano, Vitamin D3" : "Tinh chất cúc la mã tự nhiên";
                $price = rand(45, 180) * 1000;
            } elseif (str_contains($categoryName, 'sắc đẹp') || str_contains($categoryName, 'mỹ phẩm') || str_contains($categoryName, 'mặt') || str_contains($categoryName, 'chống nắng')) {
                $brand = in_array($brand, ['La Roche-Posay', 'Anessa', 'Bioderma', 'DHC', 'L\'Oreal']) ? $brand : 'La Roche-Posay';
                $names = [
                    "Sữa rửa mặt tạo bọt dịu nhẹ giảm dầu mụn {$brand} 150ml",
                    "Kem dưỡng ẩm sâu phục hồi màng bảo vệ da {$brand}",
                    "Kem chống nắng vật lý phổ rộng SPF50+ {$brand}",
                    "Tinh chất serum dưỡng trắng mờ thâm nám Niacinamide {$brand}",
                    "Nước tẩy trang sạch sâu bụi mịn micellar water 250ml"
                ];
                $name = $names[$i-1];
                $unit = ($i === 1 || $i === 5) ? "Chai" : "Tuýp";
                $dosageForm = "Kem bôi da";
                $activeIngredient = ($i === 3) ? "Zinc Oxide, Titanium Dioxide" : "Hyaluronic Acid, Vitamin B5";
                $price = rand(150, 480) * 1000;
            } elseif (str_contains($categoryName, 'tiện lợi') || str_contains($categoryName, 'Bách hóa') || str_contains($categoryName, 'bách hóa') || str_contains($categoryName, 'Khác')) {
                $brand = 'Pharmacity';
                $names = [
                    "Bông y tế Bạch Tuyết thấm hút nhanh 100g",
                    "Băng keo cá nhân Pharmacity chống thấm nước",
                    "Kẹo ngậm ho thảo dược nhân sâm cực mát lạnh",
                    "Nước suối tinh khiết đóng chai Pharmacity 500ml",
                    "Tăm bông kháng khuẩn thân tre thiên nhiên"
                ];
                $name = $names[$i-1];
                $unit = ($i === 4) ? "Chai" : ($i === 2 ? "Hộp 100 miếng" : "Gói");
                $dosageForm = "Sản phẩm bách hóa tiện lợi";
                $activeIngredient = "Chất liệu cao cấp an toàn sức khỏe";
                $price = rand(10, 45) * 1000;
            } elseif (str_contains($categoryName, 'Thiết bị') || str_contains($categoryName, 'Máy') || str_contains($categoryName, 'Nhiệt kế') || str_contains($categoryName, 'huyết áp')) {
                $brand = 'Omron';
                $names = [
                    "Máy đo huyết áp bắp tay tự động Omron HEM-7120",
                    "Nhiệt kế điện tử kỹ thuật số Omron đo trán nhanh",
                    "Máy đo đường huyết tại nhà Accu-Chek Instant",
                    "Máy xông khí dung mũi họng Omron nén khí dung",
                    "Que thử đường huyết chính hãng Accu-Chek 25 que"
                ];
                $name = $names[$i-1];
                $unit = ($i === 5) ? "Hộp 25 que" : "Bộ thiết bị";
                $dosageForm = "Thiết bị y tế";
                $activeIngredient = "Cảm biến điện tử cao tần";
                $price = ($i === 5) ? 350000 : rand(650, 1400) * 1000;
                $usage = "Đọc kỹ hướng dẫn sử dụng đi kèm trong hộp thiết bị để bảo đảm đo chính xác.";
            } elseif (str_contains($categoryName, 'Sữa') || str_contains($categoryName, 'sữa') || str_contains($categoryName, 'Sữa công thức')) {
                $brand = 'Abbott';
                $names = [
                    "Sữa bột Abbott Grow Gold dinh dưỡng phát triển chiều cao 900g",
                    "Sữa bột Meiji Infant Formula số 0 cho bé từ 0-1 tuổi 800g",
                    "Sữa bột Pediasure BA hương vani tăng cân khỏe mạnh 400g",
                    "Sữa công thức Similac 5G số 2 bảo vệ hệ miễn dịch",
                    "Sữa y tế Ensure Gold dinh dưỡng đầy đủ cân đối lon 850g"
                ];
                $name = $names[$i-1];
                $unit = "Lon thiếc";
                $dosageForm = "Sữa bột công thức";
                $activeIngredient = "HMO, DHA, Canxi, Protein, Vitamin";
                $price = rand(320, 850) * 1000;
                $usage = "Hòa tan sữa bột theo đúng tỉ lệ muỗng gạt hướng dẫn trên lon sữa bằng nước ấm.";
            } elseif (str_contains($categoryName, 'Tã') || str_contains($categoryName, 'bỉm') || str_contains($categoryName, 'ta-bim')) {
                $brand = in_array($brand, ['Huggies', 'Bobby']) ? $brand : 'Huggies';
                $names = [
                    "Tã quần em bé cao cấp {$brand} size L gói 68 miếng",
                    "Tã dán siêu thấm hút {$brand} size M gói 76 miếng",
                    "Tã quần sơ sinh {$brand} size S siêu êm thoáng khí",
                    "Tã quần cực đại {$brand} size XL gói 54 miếng",
                    "Tã quần ban đêm siêu chống tràn {$brand} size XXL"
                ];
                $name = $names[$i-1];
                $unit = "Bịch tã";
                $dosageForm = "Tã giấy vệ sinh";
                $activeIngredient = "Lớp thấm hút 3D thông minh";
                $price = rand(180, 390) * 1000;
                $usage = "Thay tã định kỳ mỗi 3-4 tiếng hoặc ngay sau khi bé đi tiêu để giữ mông bé khô thoáng.";
            } else {
                // Fallback for general categories
                $names = [
                    "Viên uống hỗ trợ sức khỏe {$categoryName} {$brand} Hộp {$i}0 viên",
                    "Dung dịch uống bổ sung đề kháng {$categoryName} hiệu {$brand}",
                    "Viên sủi tăng cường chức năng {$categoryName} {$brand}",
                    "Thực phẩm bảo vệ và hồi phục {$categoryName} từ thảo dược",
                    "Thuốc đặc trị cải thiện {$categoryName} {$brand} chính hãng"
                ];
                $name = $names[$i-1];
                $price = rand(45, 350) * 1000;
                $dosageForm = ($i === 2) ? "Dung dịch" : (($i === 3) ? "Viên sủi" : "Viên nang");
            }

            $products[] = [
                'name' => $name,
                'brand' => $brand,
                'price' => $price,
                'unit' => $unit,
                'active_ingredient' => $activeIngredient,
                'dosage_form' => $dosageForm,
                'usage' => $usage,
                'notes' => $notes,
                'requires_prescription' => $requiresPrescription,
                'sku' => strtoupper(substr($brand, 0, 3)) . rand(10000, 99999)
            ];
        }

        return $products;
    }

    /**
     * Get a matching high-quality Unsplash placeholder image URL based on category theme
     */
    private function getPlaceholderImageUrl(string $categoryName, int $index): string
    {
        // Unsplash images tailored to categories
        $images = [
            'Medicine' => [
                'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1550572017-edd951b55104?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1607619056574-7b8d3ee536b2?auto=format&fit=crop&q=80&w=400'
            ],
            'PersonalCare' => [
                'https://images.unsplash.com/photo-1526947425960-945c6e72858f?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1535585209827-a15fcdbc4c2d?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1608248597279-f99d160bfcbc?auto=format&fit=crop&q=80&w=400'
            ],
            'Baby' => [
                'https://images.unsplash.com/photo-1555252333-9f8e92e65df9?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1515488042361-404e9250afef?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1502086223501-7ea6ecd79368?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1617331140180-e8262094733a?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1596464716127-f2a82984de30?auto=format&fit=crop&q=80&w=400'
            ],
            'MedicalDevice' => [
                'https://images.unsplash.com/photo-1603398938378-e54eab446dde?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1583947215259-38e31be8751f?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1584017911766-d451b3d0e843?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1616391182219-e080b4d1043a?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1579684389782-64d84b5e905d?auto=format&fit=crop&q=80&w=400'
            ],
            'Beauty' => [
                'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1512290923902-8a9f81dc236c?auto=format&fit=crop&q=80&w=400',
                'https://images.unsplash.com/photo-1617897903246-719242758050?auto=format&fit=crop&q=80&w=400'
            ]
        ];

        $key = 'Medicine';
        if (str_contains($categoryName, 'cá nhân') || str_contains($categoryName, 'tóc') || str_contains($categoryName, 'răng') || str_contains($categoryName, 'phòng tắm') || str_contains($categoryName, 'khử mùi')) {
            $key = 'PersonalCare';
        } elseif (str_contains($categoryName, 'Bé') || str_contains($categoryName, 'trẻ em') || str_contains($categoryName, 'mẹ') || str_contains($categoryName, 'Mẹ') || str_contains($categoryName, 'tã') || str_contains($categoryName, 'bỉm') || str_contains($categoryName, 'Sữa')) {
            $key = 'Baby';
        } elseif (str_contains($categoryName, 'sắc đẹp') || str_contains($categoryName, 'mặt') || str_contains($categoryName, 'chống nắng') || str_contains($categoryName, 'Mỹ phẩm')) {
            $key = 'Beauty';
        } elseif (str_contains($categoryName, 'Thiết bị') || str_contains($categoryName, 'máy') || str_contains($categoryName, 'Máy') || str_contains($categoryName, 'Nhiệt kế')) {
            $key = 'MedicalDevice';
        }

        $list = $images[$key];
        return $list[$index % count($list)];
    }
}
