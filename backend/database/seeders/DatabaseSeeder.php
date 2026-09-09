<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'marwalawrence20@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Lawren888'),
                'email_verified_at' => now(),
                'is_admin' => true,
            ],
        );
    }
}
