<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$email = 'pharmacist@pharmacy.vn';
$user = User::where('email', $email)->first();

if (!$user) {
    echo "User not found with email: $email\n";
} else {
    echo "User found:\n";
    echo "ID: " . $user->id . "\n";
    echo "Name: " . $user->full_name . "\n";
    echo "Role: " . $user->role->value . "\n";
    echo "Status: " . ($user->is_active ? 'Active' : 'Inactive') . "\n";
    echo "Password Match (Admin123): " . (Hash::check('Admin123', $user->password) ? 'Yes' : 'No') . "\n";
}
