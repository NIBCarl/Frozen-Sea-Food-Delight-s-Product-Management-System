<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SeafoodSystemRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Product permissions
            'view products',
            'create products',
            'edit products',
            'delete products',
            
            // Order permissions
            'view all orders',
            'view own orders',
            'create orders',
            'update order status',
            'cancel orders',
            
            // Delivery permissions
            'view all deliveries',
            'view assigned deliveries',
            'create deliveries',
            'update delivery status',
            
            // Shipment permissions
            'view all shipments',
            'view own shipments',
            'create shipments',
            'confirm shipments',
            
            // User permissions
            'manage users',
            'view users',
            
            // Category permissions
            'manage categories',
            
            // Reports permissions
            'view reports',
            'export reports',
            
            // Dashboard permissions
            'view admin dashboard',
            'view supplier dashboard',
            'view customer dashboard',
            'view delivery dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Admin Role
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all()); // Admin gets all permissions

        // Create Supplier Role
        $supplier = Role::firstOrCreate(['name' => 'supplier']);
        $supplier->syncPermissions([
            'view products',
            'create products',
            'edit products',
            'view own shipments',
            'create shipments',
            'view supplier dashboard',
        ]);

        // Create Customer Role
        $customer = Role::firstOrCreate(['name' => 'customer']);
        $customer->syncPermissions([
            'view products',
            'view own orders',
            'create orders',
            'cancel orders',
            'view customer dashboard',
        ]);

        // Create Delivery Personnel Role
        $deliveryPersonnel = Role::firstOrCreate(['name' => 'delivery_personnel']);
        $deliveryPersonnel->syncPermissions([
            'view assigned deliveries',
            'update delivery status',
            'view delivery dashboard',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Created roles: admin, supplier, customer, delivery_personnel');
    }
}

