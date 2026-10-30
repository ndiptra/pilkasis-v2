<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@pilkasis.test'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => 'superadmin',
                'is_active' => true,
            ],
        );
    }
}
