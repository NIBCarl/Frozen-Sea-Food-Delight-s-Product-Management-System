<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Services\SmsGateway;
use App\Services\AndroidSmsGateway;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, AndroidSmsGateway::class);
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
            return $user->hasRole('admin') || $user->hasPermissionTo('dashboard-admin');
        });

        Gate::define('viewDashboard', function (User $user) {
            return $user->hasPermissionTo('dashboard-view') || $user->hasAnyRole(['admin', 'manager', 'user']);
        });

        // User Management Gates
        Gate::define('manageUsers', function (User $user) {
            return $user->hasRole('admin') || $user->hasPermissionTo('user-create');
        });

        // Advanced Reports Gate
        Gate::define('viewAdvancedReports', function (User $user) {
            return $user->hasAnyRole(['admin', 'manager']) || $user->hasPermissionTo('reports-advanced');
        });

        // System Settings Gate
        Gate::define('accessSystemSettings', function (User $user) {
            return $user->hasRole('admin') || $user->hasPermissionTo('system-settings');
        });

        // Stock Management Gates
        Gate::define('manageStock', function (User $user) {
            return $user->hasPermissionTo('stock-create') || $user->hasAnyRole(['admin', 'manager']);
        });

        // Product Management Gates
        Gate::define('manageProducts', function (User $user) {
            return $user->hasPermissionTo('product-create') || $user->hasAnyRole(['admin', 'manager']);
        });
    }
}
