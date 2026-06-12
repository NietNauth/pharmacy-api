<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$email = 'pharmacist@pharmacy.vn';
$password = 'Admin123';

if (Auth::attempt(['email' => $email, 'password' => $password])) {
    echo "Login simulation SUCCESSFUL for $email\n";
    $user = Auth::user();
    echo "User role: " . $user->role->value . "\n";
} else {
    echo "Login simulation FAILED for $email\n";
}
