<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductImage;

// Map of common products to their real images based on the provided names in the system
$imageMap = [
    'Vitacap' => 'https://prod-cdn.pharmacity.io/e-com/images/static/20231109121200-0-11288_1.jpg',
    'Blackmores Bio C' => 'https://prod-cdn.pharmacity.io/e-com/images/static/20231221110029-0-P14460_1.jpg',
    'Solgar Ester-C' => 'https://prod-cdn.pharmacity.io/e-com/images/static/20231011100412-0-P21639_1.jpg',
    'NIVEA' => 'https://prod-cdn.pharmacity.io/e-com/images/static/20230713101831-0-P00262_1.jpg',
    'Feliz Perfume' => 'https://prod-cdn.pharmacity.io/e-com/images/static/20230531061907-0-P25410_1.jpg'
];

$products = Product::all();
foreach ($products as $p) {
    foreach ($imageMap as $keyword => $url) {
        if (stripos($p->name, $keyword) !== false) {
            ProductImage::updateOrCreate(
                ['product_id' => $p->id, 'is_primary' => true],
                ['url' => $url, 'sort_order' => 1]
            );
            echo "Restored real image for: " . $p->name . "\n";
            continue 2;
        }
    }
}

echo "Restoration complete.\n";
