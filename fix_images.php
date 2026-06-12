<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Str;

$products = Product::limit(5)->get();
foreach ($products as $p) {
    if ($p->images()->count() == 0) {
        $p->images()->create([
            'id' => (string)Str::uuid(),
            'url' => 'https://images.unsplash.com/photo-1587854692152-cbe660dbbb88?auto=format&fit=crop&q=80&w=200',
            'is_primary' => true,
            'sort_order' => 1
        ]);
        echo "Added image for: " . $p->name . "\n";
    } else {
        echo "Product " . $p->name . " already has images.\n";
    }
}
