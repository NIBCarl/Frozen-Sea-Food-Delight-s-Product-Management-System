<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Dashboard permissions
            'dashboard-view',
            'dashboard-admin',
            
            // User management permissions
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
            'user-status-update',
            
            // Product permissions
            'product-list',
            'product-view',
            'product-create',
            'product-edit',
            'product-delete',
            'product-images-manage',
            
            // Category permissions
            'category-list',
            'category-create',
            'category-edit',
            'category-delete',
            
            // Stock permissions
            'stock-list',
            'stock-create',
            'stock-edit',
            'stock-movements-view',
            'stock-alerts-view',
            
            // Order permissions
            'order-list',
            'order-create',
            'order-view',
            'order-edit',
            'order-delete',
            
            // Delivery permissions
            'delivery-list',
            'delivery-view',
            'delivery-edit',
            'delivery-create',
            
            // Report permissions
            'reports-view',
            'reports-advanced',
            'reports-export',
            
            // System permissions
            'system-settings',
            'audit-logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create seafood-specific roles (unified system)
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $supplierRole = Role::firstOrCreate(['name' => 'supplier', 'guard_name' => 'web']);
        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $deliveryRole = Role::firstOrCreate(['name' => 'delivery_personnel', 'guard_name' => 'web']);

        // Assign permissions to roles
        
        // Admin gets all permissions
        $adminRole->syncPermissions(Permission::all());
        
        // Supplier gets product and stock management permissions
        $supplierPermissions = [
            'dashboard-view',
            'product-list', 'product-create', 'product-edit', 'product-view', 'product-images-manage',
            'category-list',
            'stock-list', 'stock-create', 'stock-movements-view', 'stock-alerts-view',
            'reports-view',
        ];
        $supplierRole->syncPermissions($supplierPermissions);
        
        // Customer gets viewing and ordering permissions
        $customerPermissions = [
            'dashboard-view',
            'product-list', 'product-view',
            'category-list',
            'order-list', 'order-create', 'order-view',
        ];
        $customerRole->syncPermissions($customerPermissions);
        
        // Delivery personnel gets delivery-specific permissions
        $deliveryPermissions = [
            'dashboard-view',
            'order-view',
            'delivery-list', 'delivery-view', 'delivery-edit',
        ];
        $deliveryRole->syncPermissions($deliveryPermissions);

        // Create default admin user if doesn't exist
        $adminUser = User::firstOrCreate([
            'email' => 'admin@seafoodinventory.com'
        ], [
            'name' => 'System Administrator',
            'username' => 'sysadmin',
            'password' => bcrypt('admin123'),
            'status' => 'active',
            'profile_completed' => true,
        ]);

        $adminUser->assignRole('admin');

        // Create default supplier user if doesn't exist  
        $supplierUser = User::firstOrCreate([
            'email' => 'supplier@seafoodinventory.com'
        ], [
            'name' => 'Seafood Supplier',
            'username' => 'mainsupplier',
            'password' => bcrypt('supplier123'),
            'status' => 'active',
            'profile_completed' => true,
        ]);

        $supplierUser->assignRole('supplier');

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Admin user: admin@seafoodinventory.com / admin123');
        $this->command->info('Supplier user: supplier@seafoodinventory.com / supplier123');
    }
}
