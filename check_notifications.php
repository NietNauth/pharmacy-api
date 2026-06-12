<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('role', 'admin')->first();
if ($user) {
    $notifications = $user->notifications()->take(5)->get();
    foreach ($notifications as $n) {
        echo "ID: {$n->id} | Read At: " . ($n->read_at ?? 'NULL') . " | Title: " . ($n->data['title'] ?? 'N/A') . PHP_EOL;
    }
}
