<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'id' => (string) Str::uuid(),
                'full_name' => 'System Admin',
                'phone' => '0987654321',
                'password_hash' => Hash::make('Password123'),
                'role' => 'admin',
                'is_verified' => true,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'pharmacist@gmail.com'],
            [
                'id' => (string) Str::uuid(),
                'full_name' => 'Dược sĩ trực ca',
                'phone' => '0123456789',
                'password_hash' => Hash::make('Password123'),
                'role' => 'pharmacist',
                'is_verified' => true,
                'is_active' => true,
            ]
        );
    }
}
