<?php
try {
    App\Models\ProductQa::create([
        'product_id' => '019de19e-870c-7188-9eef-5f46fe74bec7',
        'user_id' => null,
        'body' => 'Test',
        'is_verified_purchase' => false,
    ]);
    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
