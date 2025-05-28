<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'afaq@admin.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin->value,
        ]);

        // Create regular users
        for ($i = 1; $i <= 4; $i++) {
            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => UserRole::User->value,
            ]);
        }
    }
}
