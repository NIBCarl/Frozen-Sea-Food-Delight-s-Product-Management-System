<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure customer role exists
        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        
        // Find all users with 'user' role
        $userRole = Role::where('name', 'user')->first();
        
        if ($userRole) {
            // Get all users with 'user' role
            $usersWithUserRole = User::role('user')->get();
            
            foreach ($usersWithUserRole as $user) {
                // Remove 'user' role and assign 'customer' role
                $user->removeRole('user');
                $user->assignRole('customer');
            }
            
            // Remove the 'user' role entirely if no other dependencies exist
            $userRole->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate 'user' role
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        
        // Find all users with 'customer' role (only those we changed)
        $usersWithCustomerRole = User::role('customer')->get();
        
        foreach ($usersWithCustomerRole as $user) {
            // Only change back users who don't have other specific roles
            if (!$user->hasAnyRole(['admin', 'supplier', 'delivery_personnel'])) {
                $user->removeRole('customer');
                $user->assignRole('user');
            }
        }
    }
};
