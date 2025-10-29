<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard-view');
    }

    /**
     * Get regular user dashboard data
     */
    public function index(Request $request)
    {
        // Basic inventory statistics
        $stats = [
            'totalProducts' => Product::count(),
            'activeProducts' => Product::where('status', 'active')->count(),
            'lowStockProductsCount' => Product::whereRaw('stock_quantity <= min_stock_level')->where('min_stock_level', '>', 0)->count(),
            'totalValue' => Product::select(DB::raw('SUM(price * stock_quantity) as total_value'))->value('total_value') ?? 0,
        ];

        $recentMovements = StockMovement::with(['product:id,name', 'creator:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        $lowStockProducts = Product::whereRaw('stock_quantity <= min_stock_level')
            ->where('min_stock_level', '>', 0)
            ->orderBy('stock_quantity', 'asc')
            ->limit(5)
            ->get(['id', 'name', 'stock_quantity', 'min_stock_level']);

        $charts = [
            'categoryDistribution' => Category::withCount('products')
                ->having('products_count', '>', 0)
                ->orderBy('products_count', 'desc')
                ->get(['name', 'products_count']),

            'stockTrends' => StockMovement::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as stock_in"),
                DB::raw("SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as stock_out")
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get(),
        ];

        return response()->json(compact('stats', 'recentMovements', 'lowStockProducts', 'charts'));
    }

    /**
     * Get admin dashboard data with additional system metrics
     */
    public function adminDashboard(Request $request)
    {
        // Check if user can access admin dashboard
        if (! Gate::allows('viewAdminDashboard')) {
            return response()->json(['message' => 'Unauthorized access to admin dashboard'], 403);
        }

        // Get basic stats (same as regular dashboard)
        $basicStats = [
            'totalProducts' => Product::count(),
            'activeProducts' => Product::where('status', 'active')->count(),
            'lowStockProductsCount' => Product::whereRaw('stock_quantity <= min_stock_level')->where('min_stock_level', '>', 0)->count(),
            'totalValue' => Product::select(DB::raw('SUM(price * stock_quantity) as total_value'))->value('total_value') ?? 0,
        ];

        // Additional admin-specific stats
        $adminStats = [
            'totalUsers' => User::count(),
            'activeUsers' => User::where('status', 'active')->count(),
            'inactiveUsers' => User::where('status', 'inactive')->count(),
            'totalCategories' => Category::count(),
            'recentRegistrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'totalStockMovements' => StockMovement::count(),
            'stockMovementsThisMonth' => StockMovement::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        // Merge basic and admin stats
        $stats = array_merge($basicStats, $adminStats);

        // Recent activities (more comprehensive for admins)
        $recentMovements = StockMovement::with(['product:id,name', 'creator:id,name'])
            ->latest()
            ->limit(10) // More items for admin
            ->get();

        $recentUsers = User::with('roles')
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'email', 'created_at', 'status']);

        $lowStockProducts = Product::whereRaw('stock_quantity <= min_stock_level')
            ->where('min_stock_level', '>', 0)
            ->orderBy('stock_quantity', 'asc')
            ->limit(10) // More items for admin
            ->get(['id', 'name', 'stock_quantity', 'min_stock_level']);

        // Enhanced charts for admin
        $charts = [
            'categoryDistribution' => Category::withCount('products')
                ->having('products_count', '>', 0)
                ->orderBy('products_count', 'desc')
                ->get(['name', 'products_count']),

            'stockTrends' => StockMovement::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as stock_in"),
                DB::raw("SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as stock_out")
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get(),

            'userRegistrations' => User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as registrations')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get(),

            'usersByRole' => DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('roles.name', DB::raw('COUNT(*) as count'))
                ->groupBy('roles.name')
                ->get(),
        ];

        return response()->json(compact('stats', 'recentMovements', 'recentUsers', 'lowStockProducts', 'charts'));
    }
}
