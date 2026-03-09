<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Catalog;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(){
        $revenue = Order::whereNotNull('completed_at')->sum('total_price');

        $ordersByStatus = Order::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $orderPerStatus = [
            'pending' => $ordersByStatus['pending'] ?? 0,
            'paid' => $ordersByStatus['paid'] ?? 0,
            'shipped' => $ordersByStatus['shipped'] ?? 0,
            'completed' => $ordersByStatus['completed'] ?? 0,
        ];

        $stockPerCatalog = Catalog::select('id', 'name')
            ->withSum('items', 'stock')
            ->get();

        $ordersPerCatalog = DB::table('order_items')
            ->join('items', 'order_items.item_id', '=', 'items.id')
            ->join('catalogs', 'items.catalog_id', '=', 'catalogs.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.completed_at', '!=', null)
            ->select(
                'catalogs.id',
                'catalogs.name',
                DB::raw('SUM(order_items.qty) as total_sold')
            )
            ->groupBy('catalogs.id', 'catalogs.name')
            ->orderByDesc('total_sold')
            ->get();

        return response()->json([
            'revenue' => (float) $revenue,
            'total_orders' => Order::count(),
            'orders_per_status' => $orderPerStatus,
            'stock_per_catalog' => $stockPerCatalog,
            'orders_per_catalog' => $ordersPerCatalog
        ]);
    }

    public function sales(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month');

        $query = DB::table('orders')
            ->where('status', 'completed')
            ->whereYear('completed_at', $year);

        if ($month) {

            $sales = $query
                ->whereMonth('completed_at', $month)
                ->selectRaw('DAY(completed_at) as day, SUM(total_price) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            return response()->json([
                'year' => (int) $year,
                'month' => (int) $month,
                'data' => $sales
            ]);
        }

        $sales = $query
            ->selectRaw('MONTH(completed_at) as month, SUM(total_price) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'year' => (int) $year,
            'data' => $sales
        ]);
    }
}
