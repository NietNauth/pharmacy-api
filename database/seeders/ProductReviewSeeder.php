<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ReviewImage;
use App\Models\ReviewVote;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductReviewSeeder extends Seeder
{
    public function run()
    {
        $products = Product::limit(3)->get();
        $customers = User::where('role', 'customer')->limit(2)->get();
        $orderItems = \DB::table('order_items')->limit(10)->pluck('id')->toArray();

        if ($products->isEmpty() || $customers->isEmpty() || empty($orderItems)) {
            return;
        }

        $oiIndex = 0;
        foreach ($products as $product) {
            foreach ($customers as $index => $customer) {
                if (!isset($orderItems[$oiIndex])) break;
                
                // Create a review
                $review = ProductReview::create([
                    'id' => Str::uuid(),
                    'product_id' => $product->id,
                    'user_id' => $customer->id,
                    'order_item_id' => $orderItems[$oiIndex++],
                    'rating' => $index == 0 ? 5 : 4,
                    'title' => $index == 0 ? 'Sản phẩm rất tốt' : 'Dùng khá ổn',
                    'body' => $index == 0 
                        ? 'Tôi đã sử dụng sản phẩm này được 1 tuần và cảm thấy rất hiệu quả. Giao hàng nhanh, đóng gói cẩn thận. Sẽ tiếp tục ủng hộ PharmaVN.' 
                        : 'Sản phẩm đúng như mô tả, giá cả hợp lý. Tuy nhiên shipper giao hơi chậm một chút.',
                    'is_verified_purchase' => true,
                    'is_visible' => true,
                    'helpful_count' => $index == 0 ? 15 : 2,
                    'admin_reply' => $index == 0 ? 'Cảm ơn bạn đã tin tưởng PharmaVN! Rất vui vì sản phẩm mang lại hiệu quả cho bạn.' : null,
                    'admin_replied_at' => $index == 0 ? now() : null,
                ]);

                // Add some images for the 5-star review
                if ($index == 0) {
                    ReviewImage::create([
                        'review_id' => $review->id,
                        'url' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&q=80&w=400',
                    ]);
                    ReviewImage::create([
                        'review_id' => $review->id,
                        'url' => 'https://images.unsplash.com/photo-1576071804486-b8bc22106dbf?auto=format&fit=crop&q=80&w=400',
                    ]);
                }

                // Add some votes
                ReviewVote::create([
                    'review_id' => $review->id,
                    'user_id' => $customer->id, // User votes for their own for testing
                    'is_helpful' => true,
                ]);
            }
        }

        // Add one hidden review for testing moderation
        if (isset($orderItems[$oiIndex])) {
            ProductReview::create([
                'id' => Str::uuid(),
                'product_id' => $products->first()->id,
                'user_id' => $customers->last()->id,
                'order_item_id' => $orderItems[$oiIndex],
                'rating' => 1,
                'title' => 'Nội dung không phù hợp',
                'body' => 'Đánh giá này chứa nội dung spam hoặc vi phạm quy tắc cộng đồng nên cần bị ẩn.',
                'is_verified_purchase' => false,
                'is_visible' => false,
                'helpful_count' => 0,
            ]);
        }
    }
}
