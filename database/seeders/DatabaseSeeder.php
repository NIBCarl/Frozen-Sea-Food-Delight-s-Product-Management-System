<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call([
            RolePermissionSeeder::class,
            SampleUsersSeeder::class,
            SampleDataSeeder::class,
            SampleOrderSeeder::class,
        ]);

        // Ensure a simple test user exists as well
        User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'status' => 'active',
        ])->assignRole('customer');
    }
}
