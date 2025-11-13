<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function inventory(Request $request)
    {
        $query = Product::with(['category'])
            ->when($request->category_id, function ($q) use ($request) {
                return $q->where('category_id', $request->category_id);
            })
            ->when($request->status, function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->when($request->low_stock, function ($q) {
                return $q->whereRaw('stock_quantity <= min_stock_level');
            });

        $products = $query->orderBy('name')->get();

        return response()->json([
            'data' => $products,
            'summary' => [
                'total_products' => $products->count(),
                'total_value' => $products->sum(function ($product) {
                    return $product->stock_quantity * $product->price;
                }),
                'low_stock_count' => $products->filter(function ($product) {
                    return (int) $product->stock_quantity <= (int) $product->min_stock_level;
                })->count(),
            ]
        ]);
    }

    public function products(Request $request)
    {
        $query = Product::with(['category', 'creator'])
            ->when($request->start_date, function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->start_date);
            })
            ->when($request->end_date, function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->end_date);
            });

        $products = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $products,
            'summary' => [
                'total_products' => $products->count(),
                'active_products' => $products->where('status', 'active')->count(),
                'featured_products' => $products->where('featured', true)->count(),
            ]
        ]);
    }

    public function export(Request $request, $reportType)
    {
        $format = $request->query('format', 'excel'); // Default to excel

        $dataQuery = null;
        $viewName = '';
        $fileName = $reportType . '-report';

        if ($reportType === 'inventory') {
            $dataQuery = $this->getInventoryQuery($request);
            $viewName = 'reports.inventory-pdf';
        } elseif ($reportType === 'products') {
            $dataQuery = $this->getProductsQuery($request);
            // Assuming a view exists or will be created for product reports
            $viewName = 'reports.products-pdf'; 
        } else {
            return response()->json(['message' => 'Invalid report type'], 400);
        }

        $data = $dataQuery->get();

        switch ($format) {
            case 'pdf':
                if (empty($viewName) || !view()->exists($viewName)) {
                    return response()->json(['message' => 'PDF view not defined for this report type'], 400);
                }
                $pdf = Pdf::loadView($viewName, ['products' => $data]);
                return $pdf->download($fileName . '.pdf');

            case 'excel':
            case 'csv':
                // Fallback CSV stream to avoid dependency/version issues
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename=' . $fileName . '.csv',
                ];

                $callback = function () use ($data) {
                    $out = fopen('php://output', 'w');
                    // Header row
                    fputcsv($out, ['ID', 'Name', 'Category', 'Stock', 'Min Stock', 'Price', 'Status']);
                    foreach ($data as $product) {
                        fputcsv($out, [
                            $product->id,
                            $product->name,
                            optional($product->category)->name,
                            $product->stock_quantity,
                            $product->min_stock_level,
                            $product->price,
                            $product->status,
                        ]);
                    }
                    fclose($out);
                };

                return response()->stream($callback, 200, $headers);

            default:
                return response()->json(['message' => 'Invalid export format'], 400);
        }
    }

    private function getInventoryQuery(Request $request)
    {
        return Product::with(['category'])
            ->when($request->category_id, function ($q) use ($request) {
                return $q->where('category_id', $request->category_id);
            })
            ->when($request->status, function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->when($request->low_stock, function ($q) {
                return $q->whereRaw('stock_quantity <= min_stock_level');
            })
            ->orderBy('name');
    }

    private function getProductsQuery(Request $request)
    {
        return Product::with(['category', 'creator'])
            ->when($request->start_date, function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->start_date);
            })
            ->when($request->end_date, function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->end_date);
            })
            ->orderBy('created_at', 'desc');
    }
}
