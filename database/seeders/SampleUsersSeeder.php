<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserStatus;

class SampleUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or Update Admin User
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin User',
                'email' => 'admin@seafood.com',
                'password' => Hash::make('password123'),
                'contact_number' => '09123456789',
                'delivery_address' => 'Surigao City, Philippines',
                'status' => 'active',
                'profile_completed' => true,
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Create or Update Supplier User (Cebu-based)
        $supplier = User::updateOrCreate(
            ['username' => 'supplier'],
            [
                'name' => 'Cebu Seafood Supplier',
                'email' => 'supplier@seafood.com',
                'password' => Hash::make('password123'),
                'contact_number' => '09234567890',
                'delivery_address' => 'Cebu City, Philippines',
                'status' => 'active',
                'profile_completed' => true,
            ]
        );
        if (!$supplier->hasRole('supplier')) {
            $supplier->assignRole('supplier');
        }

        // Create or Update Customer Users
        $customer1 = User::updateOrCreate(
            ['username' => 'customer'],
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'customer@seafood.com',
                'password' => Hash::make('password123'),
                'contact_number' => '09345678901',
                'delivery_address' => '123 Main St, Surigao City',
                'status' => 'active',
                'profile_completed' => true,
            ]
        );
        if (!$customer1->hasRole('customer')) {
            $customer1->assignRole('customer');
        }

        $customer2 = User::updateOrCreate(
            ['username' => 'customer2'],
            [
                'name' => 'Maria Santos',
                'email' => 'customer2@example.com',
                'password' => Hash::make('password123'),
                'contact_number' => '09456789012',
                'delivery_address' => '456 Market St, Surigao City',
                'status' => 'active',
                'profile_completed' => true,
            ]
        );
        if (!$customer2->hasRole('customer')) {
            $customer2->assignRole('customer');
        }

        // Create or Update Delivery Personnel
        $delivery = User::updateOrCreate(
            ['username' => 'delivery'],
            [
                'name' => 'Pedro Delivery',
                'email' => 'delivery@seafood.com',
                'password' => Hash::make('password123'),
                'contact_number' => '09567890123',
                'delivery_address' => 'Surigao City, Philippines',
                'status' => 'active',
                'profile_completed' => true,
            ]
        );
        if (!$delivery->hasRole('delivery_personnel')) {
            $delivery->assignRole('delivery_personnel');
        }

        $this->command->info('Sample users created successfully!');
        $this->command->info('Admin: admin@seafood.com / password123');
        $this->command->info('Supplier: supplier@seafood.com / password123');
        $this->command->info('Customer: customer@seafood.com / password123');
        $this->command->info('Delivery: delivery@seafood.com / password123');
    }
}
