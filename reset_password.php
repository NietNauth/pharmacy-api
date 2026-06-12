<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$email = 'pharmacist@pharmacy.vn';
$user = User::where('email', $email)->first();

if ($user) {
    $user->password_hash = Hash::make('Admin123');
    $user->save();
    echo "Password for $email has been reset to Admin123 (column: password_hash)\n";
} else {
    echo "User $email not found\n";
}
