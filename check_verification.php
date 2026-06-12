<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$email = 'pharmacist@pharmacy.vn';
$user = User::where('email', $email)->first();

if ($user) {
    echo "User found:\n";
    echo "Email: " . $user->email . "\n";
    echo "Is Verified: " . ($user->is_verified ? 'Yes' : 'No') . "\n";
    echo "Is Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
} else {
    echo "User not found\n";
}
