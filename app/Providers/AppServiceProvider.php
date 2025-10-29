<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define authorization gates
        $this->registerGates();
    }

    /**
     * Register the application's authorization gates.
     */
    private function registerGates(): void
    {
        // Dashboard Gates
        Gate::define('viewAdminDashboard', function (User $user) {
            return $user->hasRole('admin') || $user->hasPermission('dashboard-admin');
        });

        Gate::define('viewDashboard', function (User $user) {
            return $user->hasPermission('dashboard-view') || $user->hasRole(['admin', 'manager', 'user']);
        });

        // User Management Gates
        Gate::define('manageUsers', function (User $user) {
            return $user->hasRole('admin') || $user->hasPermission('user-create');
        });

        // Advanced Reports Gate
        Gate::define('viewAdvancedReports', function (User $user) {
            return $user->hasRole(['admin', 'manager']) || $user->hasPermission('reports-advanced');
        });

        // System Settings Gate
        Gate::define('accessSystemSettings', function (User $user) {
            return $user->hasRole('admin') || $user->hasPermission('system-settings');
        });

        // Stock Management Gates
        Gate::define('manageStock', function (User $user) {
            return $user->hasPermission('stock-create') || $user->hasRole(['admin', 'manager']);
        });

        // Product Management Gates
        Gate::define('manageProducts', function (User $user) {
            return $user->hasPermission('product-create') || $user->hasRole(['admin', 'manager']);
        });
    }
}
