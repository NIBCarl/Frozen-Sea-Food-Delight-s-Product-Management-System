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

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Assign permissions to roles
        
        // Admin gets all permissions
        $adminRole->syncPermissions(Permission::all());
        
        // Manager gets most permissions except user management and system settings
        $managerPermissions = [
            'dashboard-view',
            'product-list', 'product-create', 'product-edit', 'product-delete', 'product-images-manage',
            'category-list', 'category-create', 'category-edit', 'category-delete',
            'stock-list', 'stock-create', 'stock-edit', 'stock-movements-view', 'stock-alerts-view',
            'reports-view', 'reports-advanced', 'reports-export',
        ];
        $managerRole->syncPermissions($managerPermissions);
        
        // Regular user gets basic permissions
        $userPermissions = [
            'dashboard-view',
            'product-list', 'product-create', 'product-edit',
            'category-list',
            'stock-list', 'stock-create', 'stock-movements-view', 'stock-alerts-view',
            'reports-view',
        ];
        $userRole->syncPermissions($userPermissions);

        // Create default admin user if doesn't exist
        $adminUser = User::firstOrCreate([
            'email' => 'admin@seafoodinventory.com'
        ], [
            'name' => 'System Administrator',
            'username' => 'admin',
            'password' => bcrypt('admin123'),
            'status' => 'active',
        ]);

        $adminUser->assignRole('admin');

        // Create default manager user if doesn't exist
        $managerUser = User::firstOrCreate([
            'email' => 'manager@seafoodinventory.com'
        ], [
            'name' => 'Inventory Manager',
            'username' => 'manager',
            'password' => bcrypt('manager123'),
            'status' => 'active',
        ]);

        $managerUser->assignRole('manager');

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Admin user: admin@seafoodinventory.com / admin123');
        $this->command->info('Manager user: manager@seafoodinventory.com / manager123');
    }
}
