<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$items = DB::table('cart_items')->get();
if ($items->isEmpty()) {
    echo "No cart items found." . PHP_EOL;
} else {
    foreach($items as $i) {
        echo "Item ID: {$i->id} | Price: {$i->price_snapshot}" . PHP_EOL;
    }
}

$orders = DB::table('orders')->orderBy('created_at', 'desc')->limit(5)->get();
echo "Recent Orders:" . PHP_EOL;
foreach($orders as $o) {
    echo "Order: {$o->order_code} | Total: {$o->total}" . PHP_EOL;
}
