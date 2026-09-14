<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL', 'superadmin@example.com');
        $password = env('SUPERADMIN_PASSWORD', 'password');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('SUPERADMIN_NAME', 'Superadmin'),
                'password' => Hash::make($password),
                'role' => User::ROLE_SUPERADMIN,
                'technical_group_id' => null,
            ]
        );
    }
}
