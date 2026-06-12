<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$count = DB::table('branches')->count();
echo "Branches Count: " . $count . PHP_EOL;

if ($count > 0) {
    $branches = DB::table('branches')->get();
    foreach($branches as $b) {
        echo " - {$b->name} (Active: {$b->is_active})" . PHP_EOL;
    }
}
