<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    
    // Test route to check Sanctum functionality
    Route::get('test-sanctum', function () {
        try {
            $user = \App\Models\User::first();
            if ($user) {
                $token = $user->createToken('test-token');
                return response()->json([
                    'message' => 'Sanctum is working properly!',
                    'token_created' => true,
                    'token_type' => class_basename($token)
                ]);
            } else {
                return response()->json([
                    'message' => 'Sanctum is working, but no users found in database'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sanctum error: ' . $e->getMessage(),
                'error' => true
            ], 500);
        }
    });
    
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login']);
        Route::post('register', [App\Http\Controllers\Api\AuthController::class, 'register']);
        Route::post('forgot-password', [App\Http\Controllers\Api\AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [App\Http\Controllers\Api\AuthController::class, 'resetPassword']);
    });

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth Management
        Route::prefix('auth')->group(function () {
            Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
            Route::post('refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh']);
            Route::get('user', [App\Http\Controllers\Api\AuthController::class, 'user']);
        });

        // User Management
        Route::apiResource('users', App\Http\Controllers\Api\UserController::class);
        Route::get('roles', [App\Http\Controllers\Api\RoleController::class, 'index'])->name('roles.index');
        Route::patch('users/{user}/status', [App\Http\Controllers\Api\UserController::class, 'updateStatus']);
        Route::post('users/{user}/avatar', [App\Http\Controllers\Api\UserController::class, 'updateAvatar']);

        // Category Management
        Route::apiResource('categories', App\Http\Controllers\Api\CategoryController::class);
        Route::get('categories/{category}/products', [App\Http\Controllers\Api\CategoryController::class, 'products']);

        // Product Management
        Route::apiResource('products', App\Http\Controllers\Api\ProductController::class);
        Route::post('products/{product}/images', [App\Http\Controllers\Api\ProductController::class, 'uploadImages']);
        Route::delete('products/{product}/images/{image}', [App\Http\Controllers\Api\ProductController::class, 'deleteImage']);
        Route::patch('products/{product}/images/{image}/primary', [App\Http\Controllers\Api\ProductController::class, 'setPrimaryImage']);

        // Stock Management
        Route::apiResource('stock-movements', App\Http\Controllers\Api\StockMovementController::class)->only(['index', 'store', 'show']);
        Route::get('products/{product}/stock-history', [App\Http\Controllers\Api\StockMovementController::class, 'productHistory']);
        Route::get('stock-alerts', [App\Http\Controllers\Api\StockMovementController::class, 'stockAlerts']);

        // Dashboard & Reports
        Route::get('dashboard', [App\Http\Controllers\Api\DashboardController::class, 'index']);
        Route::get('admin/dashboard', [App\Http\Controllers\Api\DashboardController::class, 'adminDashboard']);
        Route::get('reports/inventory', [App\Http\Controllers\Api\ReportController::class, 'inventory']);
        Route::get('reports/products', [App\Http\Controllers\Api\ReportController::class, 'products']);
        Route::get('reports/export/{reportType}', [App\Http\Controllers\Api\ReportController::class, 'export']);

        // Profile & Settings Management
        Route::prefix('profile')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\ProfileController::class, 'show']);
            Route::put('/', [App\Http\Controllers\Api\ProfileController::class, 'updateProfile']);
            Route::put('password', [App\Http\Controllers\Api\ProfileController::class, 'changePassword']);
            Route::post('avatar', [App\Http\Controllers\Api\ProfileController::class, 'updateAvatar']);
            Route::delete('avatar', [App\Http\Controllers\Api\ProfileController::class, 'removeAvatar']);
            Route::get('preferences', [App\Http\Controllers\Api\ProfileController::class, 'getPreferences']);
            Route::put('preferences', [App\Http\Controllers\Api\ProfileController::class, 'updatePreferences']);
        });

        // Order Management
        Route::apiResource('orders', App\Http\Controllers\Api\OrderController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::patch('orders/{order}/status', [App\Http\Controllers\Api\OrderController::class, 'updateStatus']);
        Route::patch('orders/{order}/verify-payment', [App\Http\Controllers\Api\OrderController::class, 'verifyPayment']);

        // Payment Receipt Management
        Route::prefix('payment-receipts')->group(function () {
            Route::post('/upload', [App\Http\Controllers\Api\PaymentReceiptController::class, 'upload']);
            Route::delete('/delete', [App\Http\Controllers\Api\PaymentReceiptController::class, 'delete']);
        });

        // Cart Management
        Route::prefix('cart')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\CartController::class, 'index']);
            Route::post('/items', [App\Http\Controllers\Api\CartController::class, 'addItem']);
            Route::put('/items/{productId}', [App\Http\Controllers\Api\CartController::class, 'updateItem']);
            Route::delete('/items/{productId}', [App\Http\Controllers\Api\CartController::class, 'removeItem']);
            Route::delete('/clear', [App\Http\Controllers\Api\CartController::class, 'clear']);
        });

        // Delivery Management
        Route::apiResource('deliveries', App\Http\Controllers\Api\DeliveryController::class)->only(['index', 'store']);
        Route::patch('deliveries/{delivery}/status', [App\Http\Controllers\Api\DeliveryController::class, 'updateStatus']);
        Route::get('deliveries/today', [App\Http\Controllers\Api\DeliveryController::class, 'todayDeliveries']);
        Route::get('deliveries/today/statistics', [App\Http\Controllers\Api\DeliveryController::class, 'todayStatistics']);
        Route::get('deliveries/history', [App\Http\Controllers\Api\DeliveryController::class, 'historyDeliveries']);

        // Shipment Management
        Route::apiResource('shipments', App\Http\Controllers\Api\ShipmentController::class)->only(['index', 'store']);
        Route::post('shipments/{shipment}/mark-arrived', [App\Http\Controllers\Api\ShipmentController::class, 'markAsArrived']);
        Route::post('shipments/{shipment}/confirm-arrival', [App\Http\Controllers\Api\ShipmentController::class, 'confirmArrival']);

        // Supplier Order Management
        Route::prefix('supplier')->group(function () {
            Route::get('orders', [App\Http\Controllers\Api\SupplierOrderController::class, 'index']);
            Route::get('orders/statistics', [App\Http\Controllers\Api\SupplierOrderController::class, 'statistics']);
            Route::get('orders/recent', [App\Http\Controllers\Api\SupplierOrderController::class, 'recentOrders']);
            Route::get('orders/{order}', [App\Http\Controllers\Api\SupplierOrderController::class, 'show']);
            Route::patch('orders/{order}/mark-ready', [App\Http\Controllers\Api\SupplierOrderController::class, 'markAsReady']);
            Route::post('orders/{order}/report-issue', [App\Http\Controllers\Api\SupplierOrderController::class, 'reportIssue']);
        });
    });
});
